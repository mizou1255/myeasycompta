<?php
namespace ECWP\Admin;

use ECWP\Admin\PDF\PDFGenerator;
use ECWP\Admin\Settings\ECWP_Settings;
use ECWP\API\Routes;

class ECWP_Invoices
{

    protected $routes;

    public function __construct()
    {
        global $wpdb;
        add_action('admin_menu', array($this, 'add_submenu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        $this->routes = new Routes();
        $this->register_api_routes();
    }

    public function add_submenu_page()
    {
        add_submenu_page(
            'my-easy-compta',
            __('Invoices', 'my-easy-compta'),
            __('Invoices', 'my-easy-compta'),
            'manage_options',
            'my-easy-compta-invoices',
            array($this, 'render_page'),
            3
        );
    }

    public function enqueue_scripts($hook_suffix)
    {
        if ('myeasycompta_page_my-easy-compta-invoices' === $hook_suffix) {
            wp_enqueue_script('my-easy-compta-invoices', ECWP_URL . '/assets/dist/invoices.min.js', array(), ECWP_VERSION, true);
        }
    }

    public function render_page()
    {
        echo '<div id="my-easy-compta-invoices-app" class="ecwp-content"></div>';
    }

    private function register_api_routes()
    {
        $this->routes->add_route('/invoices', 'GET', $this, 'get_invoices', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/invoices/(?P<id>\d+)', 'GET', $this, 'get_invoice_details', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/invoices/(?P<id>\d+)', 'PUT', $this, 'edit_invoice', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/invoices/delete/(?P<id>\d+)', 'DELETE', $this, 'delete_invoice', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/invoices', 'POST', $this, 'add_invoices', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/invoices/element-add', 'POST', $this, 'create_invoice_items_batch', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/invoices/(?P<id>\d+)/items', 'GET', $this, 'get_invoice_items', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/invoices/edit-item/(?P<id>\d+)', 'PUT', $this, 'edit_invoice_item', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/invoices/element-delete/(?P<id>\d+)', 'DELETE', $this, 'delete_invoice_item', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/invoices/update-invoice-items-order', 'POST', $this, 'update_invoice_items_order', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/invoices/update-status', 'POST', $this, 'update_invoice_status', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/invoices/item-details/(?P<id>\d+)', 'GET', $this, 'get_item_details_for_edit', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/invoices/pdf/(?P<id>\d+)', 'GET', $this, 'generate_invoice_pdf', function () {
            return current_user_can('manage_options');
        });

        $this->routes->register_routes();

    }

    public function get_invoices($request)
    {
        global $wpdb;
        $page = isset($request['page']) ? absint($request['page']) : 1;
        $per_page = isset($request['per_page']) ? absint($request['per_page']) : 10;
        $offset = ($page - 1) * $per_page;

        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        $invoices = $wpdb->get_results(
            $wpdb->prepare("SELECT invoices.id,
                        clients.company_name,
                        currencies.symbol AS currency_symbole,
                        invoices.invoice_number,
                        invoices.amount,
                        invoices.total_amount,
                        invoices.status,
                        invoices.due_date,
                        invoices.created_at
                FROM %i AS invoices
                LEFT JOIN %i AS clients ON invoices.client_id = clients.id
                LEFT JOIN %i AS currencies ON clients.currency_id = currencies.id
                ORDER BY invoices.id DESC
                LIMIT %d, %d",
                ECWP_TABLE_INVOICES, ECWP_TABLE_CLIENTS, ECWP_TABLE_CURRENCY,
                $offset, $per_page),
            OBJECT
        );

        $settings = new \ECWP\Admin\Settings\ECWP_Settings();
        $format_date_response = $settings->get_format_date();
        $format_date = isset($format_date_response->data) ? $format_date_response->data : 'Y-m-d';

        $total_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM %i", ECWP_TABLE_INVOICES));
        $total_pages = ceil($total_count / $per_page);

        $data = array();
        foreach ($invoices as $invoice) {
            $data[] = array(
                'id' => $invoice->id,
                'client_name' => $invoice->company_name,
                'client_currency' => $invoice->currency_symbole,
                'invoice_number' => $encrypt->decrypt($invoice->invoice_number),
                'amount' => number_format(floatval($encrypt->decrypt($invoice->amount)), 2, '.', ''),
                'total_amount' => number_format(floatval($encrypt->decrypt($invoice->total_amount)), 2, '.', ''),
                'status' => $encrypt->decrypt($invoice->status),
                'due_date' => date_i18n($format_date, strtotime($invoice->due_date)),
                'created' => date_i18n($format_date, strtotime($invoice->created_at)),
            );
        }

        return rest_ensure_response(array(
            'invoices' => $data,
            'total_count' => $total_count,
            'total_pages' => $total_pages,
            'page' => $page,
            'per_page' => $per_page,
        ));
    }

    public function get_invoice_details($request)
    {
        global $wpdb;
        $params = $request->get_params();
        $invoice_id = $params['id'];

        $invoice_details = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM %i WHERE id = %d", ECWP_TABLE_INVOICES, $invoice_id),
            ARRAY_A
        );

        if (!$invoice_details) {
            return new \WP_Error('invoice_not_found', __('Invoice not found.', 'my-easy-compta'), array('status' => 404));
        }

        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        $invoice_details['invoice_number'] = $encrypt->decrypt($invoice_details['invoice_number']);
        $invoice_details['client_id'] = $invoice_details['client_id'];
        $invoice_details['exchange_rate'] = number_format($encrypt->decrypt($invoice_details['exchange_rate']), 2, '.', ' ');
        $invoice_details['status'] = $encrypt->decrypt($invoice_details['status']);

        return rest_ensure_response($invoice_details);
    }

    public function add_invoices($request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        $valid_nonce = wp_verify_nonce($nonce, 'wp_rest');
        $has_permission = current_user_can('manage_options');

        if (!$valid_nonce) {
            error_log('Invalid Nonce: ' . $nonce);
            return new \WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        if (!$has_permission) {
            error_log('Permission Denied for User: ' . get_current_user_id());
            return new \WP_Error('rest_forbidden', __('API access error', 'my-easy-compta'), array('status' => 403));
        }

        global $wpdb;
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt;

        $last_invoice_id = $wpdb->get_var($wpdb->prepare("SELECT MAX(number) AS last_id FROM %i", ECWP_TABLE_INVOICES));
        if (empty($last_invoice_id)) {
            $last_invoice_id = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM %i WHERE meta_key = 'invoice_first'", ECWP_TABLE_SETTINGS));
        } else {
            $last_invoice_id = $last_invoice_id + 1;
        }

        $invoice_prefix = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM %i WHERE meta_key = 'invoice_prefix'", ECWP_TABLE_SETTINGS));
        $invoice_prefix = $invoice_prefix ? sanitize_text_field($invoice_prefix) : 'INV';
        $invoice_number = $invoice_prefix . '_' . str_pad($last_invoice_id, 4, '0', STR_PAD_LEFT);

        $invoice_data = array(
            'number' => $last_invoice_id,
            'invoice_number' => $encrypt->encrypt($invoice_number),
            'client_id' => absint($request['client_id']),
            'exchange_rate' => $encrypt->encrypt(floatval($request['exchange_rate'])),
            'status' => $encrypt->encrypt(sanitize_text_field($request['status'])),
            'due_date' => sanitize_text_field($request['date']),
            'created_at' => gmdate('Y-m-d'),
        );
        $format = array(
            '%d',
            '%s',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
        );

        $result = $wpdb->insert(
            ECWP_TABLE_INVOICES,
            $invoice_data,
            $format
        );

        if ($result === false) {
            return new \WP_Error('database_insert_error', __('Could not insert invoice into database', 'my-easy-compta'), array('status' => 500));
        }

        $invoice_id = $wpdb->insert_id;
        if ($invoice_id) {
            $invoice_data['id'] = $invoice_id;
            $invoice_data['success'] = true;
            $invoice_data['message'] = __('Invoice added successfully', 'my-easy-compta');
        } else {
            $invoice_data['success'] = false;
            $invoice_data['message'] = __('Could not insert invoice into database', 'my-easy-compta');
        }

        return rest_ensure_response($invoice_data);
    }

    public function create_invoice_items_batch(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        $valid_nonce = wp_verify_nonce($nonce, 'wp_rest');
        $has_permission = current_user_can('manage_options');

        if (!$valid_nonce) {
            error_log('Invalid Nonce: ' . $nonce);
            return new \WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        if (!$has_permission) {
            error_log('Permission Denied for User: ' . get_current_user_id());
            return new \WP_Error('rest_forbidden', __('API access error', 'my-easy-compta'), array('status' => 403));
        }

        $params = $request->get_params();
        $required_fields = ['invoice_id', 'item_name', 'quantity', 'unit_price'];

        foreach ($required_fields as $field) {
            if (empty($params[$field])) {
                return new \WP_Error(
                    'rest_missing_param',
                    sprintf(
                        /* translators: %s is the field name */
                        __('The field %s is required', 'my-easy-compta'),
                        $field
                    ),
                    array('status' => 422)
                );
            }
        }

        global $wpdb;
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        $data = [
            'invoice_id' => intval($params['invoice_id']),
            'item_name' => $encrypt->encrypt(sanitize_text_field($params['item_name'])),
            'item_ref' => $encrypt->encrypt(sanitize_text_field($params['item_ref'])),
            'item_category' => sanitize_text_field($params['item_category']),
            'item_description' => wp_kses_post($params['item_description']),
            'quantity' => $encrypt->encrypt(sanitize_text_field($params['quantity'])),
            'vat_rate' => $encrypt->encrypt(sanitize_text_field($params['vat_rate'])),
            'unit_price' => $encrypt->encrypt(sanitize_text_field($params['unit_price'])),
            'discount' => $encrypt->encrypt(sanitize_text_field($params['discount'])),
            'total_price' => $encrypt->encrypt(sanitize_text_field($params['total_price'])),
            'total_amount' => $encrypt->encrypt(sanitize_text_field($params['total_amount'])),
            'item_order' => (int) ($wpdb->get_var($wpdb->prepare("SELECT MAX(item_order) FROM %i WHERE invoice_id = %d", ECWP_TABLE_INVOICE_ELEMENTS, intval($params['invoice_id']))) + 1),
        ];

        $format = [
            '%d',
            '%s',
            '%s',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
        ];

        $result = $wpdb->insert(ECWP_TABLE_INVOICE_ELEMENTS, $data, $format);

        $existing_article = $wpdb->get_var($wpdb->prepare("SELECT id FROM %i WHERE name = %s", ECWP_TABLE_ARTICLES, $params['item_name']));

        if (!$existing_article) {
            $wpdb->insert(ECWP_TABLE_ARTICLES, array(
                'name' => sanitize_text_field($params['item_name']),
                'ref' => sanitize_text_field($params['item_ref']),
                'description' => wp_kses_post($params['item_description']),
                'unit_price' => floatval($params['unit_price']),
            ));
        }

        $calculate_amount = $this->calculate_total_amount($data['invoice_id']);

        $result_invoice = $wpdb->update(
            ECWP_TABLE_INVOICES,
            $calculate_amount,
            array('id' => $data['invoice_id']),
            array(
                '%s',
                '%s',
            ),
            array('%d')
        );

        if ($result && $result_invoice) {
            return new \WP_REST_Response(array('success' => true, 'message' => __('Invoice item successfully added', 'my-easy-compta')), 200);
        } else {
            return new \WP_REST_Response(array('success' => false, 'message' => __('Failed to add invoice item', 'my-easy-compta'), '$result' => $result, '$result_invoice' => $result_invoice), 500);
        }
    }

    public function edit_invoice(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', __('Nonce verification failed.', 'my-easy-compta'), array('status' => 403));
        }
        $invoice_id = $request->get_param('id');
        if (!$invoice_id || !is_numeric($invoice_id)) {
            return new \WP_Error('invalid_invoice_id', __('Invalid invoice ID', 'my-easy-compta'), array('status' => 400));
        }

        $params = $request->get_params();

        $invoice_date = sanitize_text_field($params['due_date']);
        $client_id = intval($params['client_id']);
        $exchange_rate = isset($params['exchange_rate']) ? floatval($params['exchange_rate']) : 0;
        $status = sanitize_text_field($params['status']);

        global $wpdb;
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        $updated = $wpdb->update(
            ECWP_TABLE_INVOICES,
            array(
                'due_date' => $invoice_date,
                'client_id' => $client_id,
                'exchange_rate' => $encrypt->encrypt($exchange_rate),
                'status' => $encrypt->encrypt($status),
            ),
            array('id' => $invoice_id),
            array('%s', '%d', '%s', '%s'),
            array('%d')
        );

        if ($updated === false) {
            return new \WP_Error('db_update_error', __('Failed to update invoice', 'my-easy-compta'), array('status' => 500));
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Invoice updated successfully', 'my-easy-compta'),
            'id' => $invoice_id,
        ));
    }

    public function delete_invoice(\WP_REST_Request $request)
    {
        // Verify nonce
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        // Validate ID
        $invoice_id = $request->get_param('id');
        if (!is_numeric($invoice_id)) {
            return new \WP_Error('invalid_invoice_id', __('Invalid invoice ID.', 'my-easy-compta'), array('status' => 400));
        }

        global $wpdb;
        $wpdb->query('START TRANSACTION');
        $delete_items = $wpdb->delete(ECWP_TABLE_INVOICE_ELEMENTS, array('invoice_id' => $invoice_id));
        $delete_invoice = $wpdb->delete(ECWP_TABLE_INVOICES, array('id' => $invoice_id));

        if ($delete_invoice !== false && $delete_items !== false) {
            $wpdb->query('COMMIT');
            return new \WP_REST_Response(array('success' => true, 'message' => __('Invoice and related items successfully deleted', 'my-easy-compta')), 200);
        } else {
            $wpdb->query('ROLLBACK');
            return new \WP_Error('delete_failed', __('Failure to delete invoice and/or associated items', 'my-easy-compta'), array('status' => 500));
        }
    }
    public function get_invoice_items(\WP_REST_Request $request)
    {
        global $wpdb;
        $params = $request->get_params();
        $invoice_id = $params['id'];

        // Instancier la classe de cryptage
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        $items = $wpdb->get_results(
            $wpdb->prepare("SELECT
            ie.id,
            ie.item_name,
            ie.item_ref,
            ie.item_category,
            ac.name AS category_name,
            ie.item_description,
            ie.quantity,
            ie.vat_rate,
            ie.unit_price,
            ie.discount,
            ie.total_price,
            ie.total_amount,
            ie.item_order
        FROM
        %i ie
        LEFT JOIN
        %i ac
        ON
            ie.item_category = ac.id
        WHERE
            ie.invoice_id = %d
        ORDER BY
            ie.item_order ASC",
                ECWP_TABLE_INVOICE_ELEMENTS, ECWP_TABLE_ARTICLES_CATEGORIES,
                $invoice_id),
            ARRAY_A
        );

        if (!$items) {
            return new \WP_Error('no_items_found', __('No items found for this invoice.', 'my-easy-compta'), array('status' => 404));
        }

        foreach ($items as &$item) {
            $item['item_name'] = $encrypt->decrypt($item['item_name']);
            $item['item_ref'] = $encrypt->decrypt($item['item_ref']);
            $item['item_description'] = $item['item_description'];
            $item['quantity'] = (int) $encrypt->decrypt($item['quantity']);
            $item['vat_rate'] = (int) $encrypt->decrypt($item['vat_rate']);
            $item['unit_price'] = number_format((float) $encrypt->decrypt($item['unit_price']), 2, '.', '');
            $item['discount'] = (int) $encrypt->decrypt($item['discount']);
            $item['total_price'] = number_format((float) $encrypt->decrypt($item['total_price']), 2, '.', '');
            $item['total_amount'] = number_format((float) $encrypt->decrypt($item['total_amount']), 2, '.', '');
        }

        return rest_ensure_response($items);
    }

    public function get_item_details_for_edit(\WP_REST_Request $request)
    {
        global $wpdb;
        $params = $request->get_params();
        $item_id = $params['id'];

        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        $item_details = $wpdb->get_row(
            $wpdb->prepare("SELECT id, item_name, item_ref, item_description, quantity, vat_rate, unit_price, discount, total_price, total_amount, item_order FROM %i WHERE id = %d ORDER BY item_order ASC", ECWP_TABLE_INVOICE_ELEMENTS,
                $item_id),
            ARRAY_A
        );

        if (!$item_details) {
            return new \WP_Error('no_item_details_found', __('No item details found.', 'my-easy-compta'), array('status' => 404));
        }

        // Décrypter les champs
        $item_details['item_name'] = $encrypt->decrypt($item_details['item_name']);
        $item_details['item_ref'] = $encrypt->decrypt($item_details['item_ref']);
        $item_details['item_description'] = $item_details['item_description'];
        $item_details['quantity'] = (int) $encrypt->decrypt($item_details['quantity']);
        $item_details['vat_rate'] = (int) $encrypt->decrypt($item_details['vat_rate']);
        $item_details['unit_price'] = number_format((float) $encrypt->decrypt($item_details['unit_price']), 2, '.', '');
        $item_details['discount'] = (int) $encrypt->decrypt($item_details['discount']);
        $item_details['total_price'] = number_format((float) $encrypt->decrypt($item_details['total_price']), 2, '.', '');
        $item_details['total_amount'] = number_format((float) $encrypt->decrypt($item_details['total_amount']), 2, '.', '');

        return rest_ensure_response($item_details);
    }

    public function edit_invoice_item(\WP_REST_Request $request)
    {
        global $wpdb;
        $item_id = $request['id'];

        // Validation de l'ID de l'élément
        if (empty($item_id) || !is_numeric($item_id)) {
            return new \WP_Error('invalid_item_id', __('Invalid item ID.', 'my-easy-compta'), array('status' => 400));
        }
        $item_id = absint($item_id);

        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        $item_name = sanitize_text_field($request['item_name']);
        $item_ref = sanitize_text_field($request['item_ref']);
        $item_description = wp_kses_post($request['item_description']);
        $quantity = absint($request['quantity']);
        $vat_rate = absint($request['vat_rate']);
        $unit_price = floatval($request['unit_price']);
        $discount = absint($request['discount']);
        $total = ($quantity * $unit_price);
        $total_price = $discount ? ($total - ($total * $discount / 100)) : $total;

        $vat_rate_total = ($total_price * $vat_rate) / 100;
        $total_amount = $vat_rate_total + $total_price;

        // Chiffrer les données avant la mise à jour
        $encrypted_data = array(
            'item_name' => $encrypt->encrypt($item_name),
            'item_ref' => $encrypt->encrypt($item_ref),
            'item_description' => $item_description,
            'quantity' => $encrypt->encrypt($quantity),
            'vat_rate' => $encrypt->encrypt($vat_rate),
            'unit_price' => $encrypt->encrypt($unit_price),
            'discount' => $encrypt->encrypt($discount),
            'total_price' => $encrypt->encrypt($total_price),
            'total_amount' => $encrypt->encrypt($total_amount),
        );

        $result = $wpdb->update(
            ECWP_TABLE_INVOICE_ELEMENTS,
            $encrypted_data,
            array('id' => $item_id),
            array(
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ),
            array('%d')
        );

        if ($result === false) {
            return new \WP_REST_Response(array('success' => false, 'message' => __('Failed to edit item', 'my-easy-compta')), 500);
        }

        $invoice_id = $wpdb->get_var($wpdb->prepare("SELECT invoice_id FROM %i WHERE id = %d", ECWP_TABLE_INVOICE_ELEMENTS, $item_id));
        if (!$invoice_id) {
            return new \WP_Error('no_invoice_found', __('No invoice found for the given item.', 'my-easy-compta'), array('status' => 404));
        }

        $calculate_amount = $this->calculate_total_amount($invoice_id);

        $result_invoice = $wpdb->update(
            ECWP_TABLE_INVOICES,
            $calculate_amount,
            array('id' => $invoice_id),
            array(
                '%f',
                '%f',
            ),
            array('%d')
        );

        return new \WP_REST_Response(array('success' => true, 'message' => __('Item edited successfully', 'my-easy-compta')), 200);
    }

    public function delete_invoice_item($request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        $valid_nonce = wp_verify_nonce($nonce, 'wp_rest');
        $has_permission = current_user_can('manage_options');

        if (!$valid_nonce) {
            error_log('Invalid Nonce: ' . $nonce);
            return new \WP_Error('rest_nonce_invalid', __('Nonce invalide', 'my-easy-compta'), array('status' => 403));
        }

        if (!$has_permission) {
            error_log('Permission Denied for User: ' . get_current_user_id());
            return new \WP_Error('rest_forbidden', __('Error API access', 'my-easy-compta'), array('status' => 403));
        }

        global $wpdb;
        $item_id = absint($request->get_param('id'));

        $invoice_id = $wpdb->get_var($wpdb->prepare("SELECT invoice_id FROM %i WHERE id = %d", ECWP_TABLE_INVOICE_ELEMENTS, $item_id));
        if (!$invoice_id) {
            return new \WP_Error('no_invoice_found', __('No invoice found for the given item.', 'my-easy-compta'), array('status' => 404));
        }

        $result = $wpdb->delete(
            ECWP_TABLE_INVOICE_ELEMENTS,
            array('id' => $item_id),
            array('%d')
        );

        if ($result === false) {
            return new \WP_Error('db_error', __('Failed to delete invoice item.', 'my-easy-compta'), array('status' => 500));
        }

        $calculate_amount = $this->calculate_total_amount($invoice_id);

        $result_invoice = $wpdb->update(
            ECWP_TABLE_INVOICES,
            $calculate_amount,
            array('id' => $invoice_id),
            array(
                '%f',
                '%d',
                '%f',
            ),
            array('%d')
        );

        return rest_ensure_response(array('success' => true, 'message' => __('Invoice item deleted.', 'my-easy-compta')));
    }

    public function update_invoice_items_order(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        $valid_nonce = wp_verify_nonce($nonce, 'wp_rest');
        $has_permission = current_user_can('manage_options');

        if (!$valid_nonce || !$has_permission) {
            return new \WP_Error('unauthorized', __('Unauthorized request', 'my-easy-compta'), array('status' => 401));
        }

        $order = absint($request->get_param('order'));

        if (!is_array($order)) {
            return new \WP_Error('invalid_order', __('Invalid order data', 'my-easy-compta'), array('status' => 400));
        }

        global $wpdb;

        foreach ($order as $index => $item_id) {
            $wpdb->update(
                ECWP_TABLE_INVOICE_ELEMENTS,
                array('item_order' => $index),
                array('id' => $item_id)
            );
        }

        return new \WP_REST_Response(array('success' => true, 'message' => __('Invoice items order updated successfully', 'my-easy-compta')), 200);
    }

    public function update_invoice_status(\WP_REST_Request $request)
    {
        $id = absint($request->get_param('id'));
        $status = sanitize_text_field($request->get_param('status'));
        $method = sanitize_text_field($request->get_param('method'));

        global $wpdb;

        if (!in_array($status, ['unpaid', 'paid', 'cancelled'])) {
            return new \WP_Error('invalid_status', 'Invalid status provided', array('status' => 400));
        }

        if (!is_numeric($id)) {
            return new \WP_Error('invalid_id', __('Invalid ID provided', 'my-easy-compta'), array('status' => 400));
        }
        $invoice = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM %i WHERE id = %d", ECWP_TABLE_INVOICES, $id));
        if (null === $invoice) {
            return new \WP_Error('invalid_id', __('Invoice not found', 'my-easy-compta'), array('status' => 404));
        }

        $result = $wpdb->update(
            ECWP_TABLE_INVOICES,
            array('status' => $status),
            array('id' => $id),
            array('%s'),
            array('%d')
        );

        if ($result === false) {
            return new \WP_Error('rest_db_update_error', __('Failed to update invoice status in database', 'my-easy-compta'), array('status' => 500));
        }

        if ($status === 'paid') {
            $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt;

            $invoice = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", ECWP_TABLE_INVOICES, $id));

            $amount_invoice = $encrypt->decrypt($invoice->total_amount);
            $client_currency = $wpdb->get_var($wpdb->prepare("SELECT currency_id FROM %i WHERE id = %d", ECWP_TABLE_CLIENTS, $invoice->client_id));

            $settings = new ECWP_Settings();
            $default_currency_id = $settings->get_setting('default_currency');
            if ($client_currency != $default_currency_id) {
                $exchange_rate = $encrypt->decrypt($invoice->exchange_rate) ?? 1;
                if (!$exchange_rate) {
                    return new \WP_Error('currency_exchange_error', __('Failed to retrieve exchange rate for client currency', 'my-easy-compta'), array('status' => 500));
                }

                $amount_invoice *= $exchange_rate;
            }

            $payment_data = array(
                'invoice_id' => $id,
                'amount' => $amount_invoice,
                'payment_date' => current_time('mysql'),
                'client_id' => $invoice->client_id,
                //'payment_method_id' => $method,
                'payment_method_id' => 1,
            );

            $result = $wpdb->insert(
                ECWP_TABLE_PAYMENTS,
                $payment_data,
                array(
                    '%d',
                    '%f',
                    '%s',
                    '%d',
                    '%d',
                )
            );

            if ($result === false) {
                return new \WP_Error('rest_db_insert_error', __('Failed to add payment to database', 'my-easy-compta'), array('status' => 500));
            }
        }

        return rest_ensure_response(array('success' => true, 'message' => __('Invoice status updated successfully', 'my-easy-compta')));
    }

    public function calculate_total_amount($invoice_id)
    {
        global $wpdb;
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        $encrypted_prices = $wpdb->get_col($wpdb->prepare("SELECT total_price FROM %i WHERE invoice_id = %d", ECWP_TABLE_INVOICE_ELEMENTS, $invoice_id));
        $encrypted_amounts = $wpdb->get_col($wpdb->prepare("SELECT total_amount FROM %i WHERE invoice_id = %d", ECWP_TABLE_INVOICE_ELEMENTS, $invoice_id));

        $amount = 0;
        $totalAmount = 0;
        foreach ($encrypted_prices as $encrypted_price) {
            $price = $encrypt->decrypt($encrypted_price);
            $amount += floatval($price);
        }
        foreach ($encrypted_amounts as $encrypted_amount) {
            $amount = $encrypt->decrypt($encrypted_amount);
            $totalAmount += floatval($amount);
        }

        $data = array(
            'amount' => $encrypt->encrypt($amount),
            'total_amount' => $encrypt->encrypt($totalAmount),
        );

        return $data;
    }

    public function generate_invoice_pdf(\WP_REST_Request $request)
    {
        global $wpdb;
        $invoice_id = $request->get_param('id');
        $currency_id = $request->get_param('currency_id');

        $pdfGenerator = new PDFGenerator($wpdb);
        $pdfGenerator->generateInvoicePDF($invoice_id, "", $currency_id);
    }

}
