<?php
namespace ECWP\Admin;

use ECWP\Admin\PDF\PDFGenerator;
use ECWP\Admin\Settings\ECWP_Settings;
use ECWP\API\Routes;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class ECWP_Quotes
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
            __('Quotes', 'my-easy-compta'),
            __('Quotes', 'my-easy-compta'),
            'manage_options',
            'my-easy-compta-quotes',
            array($this, 'render_page'),
            3
        );
    }

    public function enqueue_scripts($hook_suffix)
    {
        if ('myeasycompta_page_my-easy-compta-quotes' === $hook_suffix) {
            wp_enqueue_script('my-easy-compta-quotes', ECWP_URL . '/assets/dist/quotes.min.js', array(), ECWP_VERSION, true);
        }
    }

    public function render_page()
    {
        echo '<div id="my-easy-compta-quotes-app" class="ecwp-content"></div>';
    }

    private function register_api_routes()
    {
        $this->routes->add_route('/quotes', 'GET', $this, 'get_quotes', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/quotes/(?P<id>\d+)', 'GET', $this, 'get_quote_details', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/quotes', 'POST', $this, 'add_quotes', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/quotes/element-add', 'POST', $this, 'create_quote_items_batch', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/quotes/(?P<id>\d+)', 'PUT', $this, 'edit_quote', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/quotes/delete/(?P<id>\d+)', 'DELETE', $this, 'delete_quote', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/quotes/duplicate/(?P<id>\d+)', 'POST', $this, 'duplicate_quote', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/quotes/(?P<id>\d+)/items', 'GET', $this, 'get_quote_items', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/quotes/edit-item/(?P<id>\d+)', 'PUT', $this, 'edit_quote_item', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/quotes/element-delete/(?P<id>\d+)', 'DELETE', $this, 'delete_quote_item', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/quotes/update-quote-items-order', 'POST', $this, 'update_quote_items_order', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/quotes/update-status', 'POST', $this, 'update_quote_status', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/quotes/item-details/(?P<id>\d+)', 'GET', $this, 'get_item_details_for_edit', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/quotes/convert-quote/(?P<id>\d+)', 'POST', $this, 'convert_quote_to_invoice', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/quotes/pdf/(?P<id>\d+)', 'GET', $this, 'generate_quote_pdf', function () {
            return current_user_can('manage_options');
        });

        $this->routes->register_routes();
    }

    public function get_quotes($request)
    {
        global $wpdb;
        $page = isset($request['page']) ? absint($request['page']) : 1;
        $per_page = isset($request['per_page']) ? absint($request['per_page']) : 10;
        $offset = ($page - 1) * $per_page;

        $quotes = $wpdb->get_results(
            $wpdb->prepare("SELECT quotes.id,
                        clients.company_name,
                        currencies.symbol AS currency_symbole,
                        quotes.quote_number,
                        quotes.total_amount,
                        quotes.status,
                        quotes.due_date,
                        quotes.provisional_start_date,
                        quotes.created_at
                FROM %i AS quotes
                LEFT JOIN %i AS clients ON quotes.client_id = clients.id
                LEFT JOIN %i AS currencies ON clients.currency_id = currencies.id
                ORDER BY quotes.id DESC
                LIMIT %d, %d",
                ECWP_TABLE_QUOTES, ECWP_TABLE_CLIENTS, ECWP_TABLE_CURRENCY,
                $offset, $per_page),
            OBJECT);

        $total_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM %i", ECWP_TABLE_QUOTES));
        $total_pages = ceil($total_count / $per_page);

        $settings = new \ECWP\Admin\Settings\ECWP_Settings();
        $format_date_response = $settings->get_format_date();
        $format_date = isset($format_date_response->data) ? $format_date_response->data : 'Y-m-d';

        $data = array();
        foreach ($quotes as $quote) {
            $data[] = array(
                'id' => $quote->id,
                'client_name' => $quote->company_name,
                'client_currency' => $quote->currency_symbole,
                'quote_number' => $quote->quote_number,
                'total_amount' => $quote->total_amount,
                'status' => $quote->status,
                'due_date' => date_i18n($format_date, strtotime($quote->due_date)),
                'provisional_start_date' => date_i18n($format_date, strtotime($quote->provisional_start_date)),
                'created' => date_i18n($format_date, strtotime($quote->created_at)),
            );
        }

        return rest_ensure_response(array(
            'quotes' => $data,
            'total_count' => $total_count,
            'total_pages' => $total_pages,
            'page' => $page,
            'per_page' => $per_page,
        ));
    }

    public function get_quote_details($request)
    {
        global $wpdb;
        $params = $request->get_params();
        $quote_id = $params['id'];

        $quote_details = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM %i WHERE id = %d", ECWP_TABLE_QUOTES, $quote_id),
            ARRAY_A
        );

        if (!$quote_details) {
            return new WP_Error('quote_not_found', __('Quote not found.', 'my-easy-compta'), array('status' => 404));
        }

        return rest_ensure_response($quote_details);
    }

    public function add_quotes($request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        $valid_nonce = wp_verify_nonce($nonce, 'wp_rest');
        $has_permission = current_user_can('manage_options');

        if (!$valid_nonce) {
            error_log('Invalid Nonce: ' . $nonce);
            return new WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        if (!$has_permission) {
            error_log('Permission Denied for User: ' . get_current_user_id());
            return new WP_Error('rest_forbidden', __('API access error', 'my-easy-compta'), array('status' => 403));
        }
        global $wpdb;
        $last_quote_id = $wpdb->get_var($wpdb->prepare("SELECT MAX(number) AS last_id FROM %i", ECWP_TABLE_QUOTES));
        if (empty($last_quote_id)) {
            $last_quote_id = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM %i WHERE meta_key = 'quote_first'", ECWP_TABLE_SETTINGS));
        } else {
            $last_quote_id = $last_quote_id + 1;
        }

        $quote_prefix = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM %i WHERE meta_key = 'quote_prefix'", ECWP_TABLE_SETTINGS));
        $quote_prefix = $quote_prefix ? sanitize_text_field($quote_prefix) : 'INV';
        $quote_number = $quote_prefix . '_' . str_pad($last_quote_id, 4, '0', STR_PAD_LEFT);

        $quote_data = array(
            'number' => $last_quote_id,
            'quote_number' => $quote_number,
            'due_date' => sanitize_text_field($request['due_date']),
            'provisional_start_date' => sanitize_text_field($request['provisional_start_date']),
            'client_id' => sanitize_text_field($request['client_id']),
            'status' => sanitize_text_field($request['status']),
            'created_at' => gmdate('Y-m-d'),
        );

        $result = $wpdb->insert(ECWP_TABLE_QUOTES, $quote_data);

        if ($result === false) {
            return new WP_Error('database_insert_error', __('Could not insert quote into database', 'my-easy-compta'), array('status' => 500));
        }

        $quote_id = $wpdb->insert_id;
        if ($quote_id) {
            $quote_data['id'] = $quote_id;
            $quote_data['success'] = true;
            $quote_data['message'] = __('Quote added successfully', 'my-easy-compta');
            do_action('ecwp_add_planning_quote', $quote_data);
        } else {
            $quote_data['success'] = false;
            $quote_data['message'] = __('Could not insert quote into database', 'my-easy-compta');
        }

        return rest_ensure_response($quote_data);
    }

    public function create_quote_items_batch(WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        $valid_nonce = wp_verify_nonce($nonce, 'wp_rest');
        $has_permission = current_user_can('manage_options');

        if (!$valid_nonce) {
            error_log('Invalid Nonce: ' . $nonce);
            return new WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        if (!$has_permission) {
            error_log('Permission Denied for User: ' . get_current_user_id());
            return new WP_Error('rest_forbidden', __('Error API access', 'my-easy-compta'), array('status' => 403));
        }

        $params = $request->get_params();
        $required_fields = ['quote_id', 'item_name', 'quantity', 'unit_price'];

        foreach ($required_fields as $field) {
            if (empty($params[$field])) {
                return new WP_Error(
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
        $data = [
            'quote_id' => sanitize_text_field($params['quote_id']),
            'item_name' => sanitize_text_field($params['item_name']),
            'item_ref' => sanitize_text_field($params['item_ref']),
            'item_category' => sanitize_text_field($params['item_category']),
            'item_description' => sanitize_textarea_field($params['item_description'] ?? ''),
            'quantity' => intval($params['quantity']),
            'vat_rate' => intval($params['vat_rate']),
            'unit_price' => floatval($params['unit_price']),
            'discount' => intval($params['discount']),
            'total_price' => floatval($params['total_price']),
            'total_amount' => floatval($params['total_amount']),
            'item_order' => (int) ($wpdb->get_var($wpdb->prepare("SELECT MAX(item_order) FROM %i WHERE quote_id = %d", ECWP_TABLE_QUOTE_ELEMENTS, intval($params['quote_id']))) + 1),
        ];

        $result = $wpdb->insert(ECWP_TABLE_QUOTE_ELEMENTS, $data);

        $existing_article = $wpdb->get_var($wpdb->prepare("SELECT id FROM %i WHERE name = %s", ECWP_TABLE_ARTICLES, $params['item_name']));

        if (!$existing_article) {
            $wpdb->insert(ECWP_TABLE_ARTICLES, array(
                'name' => $params['item_name'],
                'ref' => $params['item_ref'],
                'description' => $params['item_description'],
                'unit_price' => $params['unit_price'],
            ));
        }

        $calculate_amount = $this->calculate_total_amount($data['quote_id']);

        $result_quote = $wpdb->update(
            ECWP_TABLE_QUOTES,
            $calculate_amount,
            array('id' => $data['quote_id']),
            array(
                '%f',
                '%d',
                '%f',
            ),
            array('%d')
        );

        if ($result && $result_quote) {
            return new WP_REST_Response(array('success' => true, 'message' => __('Quote item successfully added', 'my-easy-compta')), 200);
        } else {
            return new WP_REST_Response(array('success' => false, 'message' => __('Failed to add quote item', 'my-easy-compta')), 500);
        }
    }

    public function get_quote_items($request)
    {
        global $wpdb;
        $quote_id = $request['id'];
        if (!isset($quote_id) || !is_numeric($quote_id)) {
            return new WP_Error('invalid_quote_id', 'Invalid quote ID.', array('status' => 400));
        }

        if (!current_user_can('manage_options')) {
            return new WP_Error('unauthorized_access', 'You are not authorized to access this resource.', array('status' => 403));
        }

        $params = $request->get_params();
        $quote_id = $params['id'];

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
                ie.quote_id = %d
            ORDER BY
                ie.item_order ASC",
                ECWP_TABLE_QUOTE_ELEMENTS, ECWP_TABLE_ARTICLES_CATEGORIES,
                $quote_id),
            ARRAY_A
        );

        if (!$items) {
            return new \WP_Error('no_items_found', __('No items found for this quote.', 'my-easy-compta'), array('status' => 404));
        }

        return rest_ensure_response($items);
    }

    public function get_item_details_for_edit($request)
    {
        global $wpdb;
        $params = $request->get_params();
        $item_id = $params['id'];

        $item_details = $wpdb->get_row(
            $wpdb->prepare("SELECT id, item_name, item_ref, item_description, quantity, vat_rate, unit_price, discount, total_price, total_amount, item_order FROM %i WHERE id = %d ORDER BY item_order ASC",
                ECWP_TABLE_QUOTE_ELEMENTS,
                $item_id),
            ARRAY_A
        );

        if (!$item_details) {
            return new WP_Error('no_item_deatils_found', __('No items found for this quote.', 'my-easy-compta'), array('status' => 404));
        }

        return rest_ensure_response($item_details);
    }

    public function delete_quote(WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        $quote_id = $request->get_param('id');
        if (!is_numeric($quote_id)) {
            return new WP_Error('invalid_quote_id', __('Invalid quote ID.', 'my-easy-compta'), array('status' => 400));
        }

        global $wpdb;
        $wpdb->query('START TRANSACTION');

        $delete_items = $wpdb->delete(ECWP_TABLE_QUOTE_ELEMENTS, array('quote_id' => $quote_id));
        $delete_quote = $wpdb->delete(ECWP_TABLE_QUOTES, array('id' => $quote_id));

        if ($delete_quote !== false && $delete_items !== false) {
            $wpdb->query('COMMIT');
            return new WP_REST_Response(array('success' => true, 'message' => __('Quote and related items successfully deleted', 'my-easy-compta')), 200);
        } else {
            $wpdb->query('ROLLBACK');
            return new WP_Error('delete_failed', __('Failure to delete Quote and/or associated items', 'my-easy-compta'), array('status' => 500));
        }
    }

    public function duplicate_quote(WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_nonce_invalid', __('Invalid nonce', 'easy-compta'), array('status' => 403));
        }

        $quote_id = $request->get_param('id');
        if (!is_numeric($quote_id)) {
            return new WP_Error('invalid_quote_id', __('Invalid quote ID.', 'easy-compta'), array('status' => 400));
        }

        global $wpdb;
        $wpdb->query('START TRANSACTION');
        $original_quote = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", ECWP_TABLE_QUOTES, $quote_id), ARRAY_A);

        if (!$original_quote) {
            return new WP_Error('quote_not_found', __('Quote not found.', 'easy-compta'), array('status' => 404));
        }

        unset($original_quote['id']);
        $last_quote_id = $wpdb->get_var($wpdb->prepare("SELECT MAX(number) AS last_id FROM %i", ECWP_TABLE_QUOTES));
        if (empty($last_quote_id)) {
            $last_quote_id = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM %i WHERE meta_key = 'quote_first'", ECWP_TABLE_SETTINGS));
        } else {
            $last_quote_id = $last_quote_id + 1;
        }

        $quote_prefix = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM %i WHERE meta_key = 'quote_prefix'", ECWP_TABLE_SETTINGS));
        $quote_prefix = $quote_prefix ? sanitize_text_field($quote_prefix) : 'INV';
        $quote_number = $quote_prefix . '_' . str_pad($last_quote_id, 4, '0', STR_PAD_LEFT);

        $original_quote['number'] = $last_quote_id;
        $original_quote['quote_number'] = $quote_number;
        $insert_quote = $wpdb->insert(ECWP_TABLE_QUOTES, $original_quote);

        if ($insert_quote === false) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('quote_duplicate_failed', __('Failed to duplicate quote.', 'easy-compta'), array('status' => 500));
        }

        $new_quote_id = $wpdb->insert_id;

        $original_items = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i WHERE quote_id = %d", ECWP_TABLE_QUOTE_ELEMENTS, $quote_id), ARRAY_A);

        foreach ($original_items as $item) {
            unset($item['id']);
            $item['quote_id'] = $new_quote_id;
            $insert_item = $wpdb->insert(ECWP_TABLE_QUOTE_ELEMENTS, $item);

            if ($insert_item === false) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('quote_item_duplicate_failed', __('Failed to duplicate quote items.', 'easy-compta'), array('status' => 500));
            }
        }
        $wpdb->query('COMMIT');

        return new WP_REST_Response(array('success' => true, 'message' => __('Quote and related items successfully duplicated.', 'easy-compta'), 'new_quote_id' => $new_quote_id), 200);
    }

    public function edit_quote(WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        $valid_nonce = wp_verify_nonce($nonce, 'wp_rest');
        $has_permission = current_user_can('manage_options');

        if (!$valid_nonce) {
            error_log('Invalid Nonce: ' . $nonce);
            return new WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        if (!$has_permission) {
            error_log('Permission Denied for User: ' . get_current_user_id());
            return new WP_Error('rest_forbidden', __('API access error', 'my-easy-compta'), array('status' => 403));
        }

        $quote_id = $request->get_param('id');

        if (!$quote_id || !is_numeric($quote_id)) {
            return new WP_Error('invalid_quote_id', 'Invalid quote ID', array('status' => 400));
        }

        $params = $request->get_params();

        $quote_date = sanitize_text_field($params['due_date']);
        $provisional_start_date = sanitize_text_field($params['provisional_start_date']);
        $client_id = intval($params['client_id']);
        $status = sanitize_text_field($params['status']);

        global $wpdb;

        $updated = $wpdb->update(
            ECWP_TABLE_QUOTES,
            array(
                'due_date' => $quote_date,
                'provisional_start_date' => $provisional_start_date,
                'client_id' => $client_id,
                'status' => $status,
            ),
            array('id' => $quote_id),
            array('%s', '%s', '%d', '%s'),
            array('%d')
        );

        if ($updated === false) {
            return new WP_Error('db_update_error', 'Failed to update quote', array('status' => 500));
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Quote updated successfully',
            'id' => $quote_id,
        ));
    }

    public function edit_quote_item(WP_REST_Request $request)
    {
        global $wpdb;
        $item_id = $request['id'];
        if (empty($item_id) || !is_numeric($item_id)) {
            return new WP_Error('invalid_Item_id', 'ID client invalid.', array('status' => 400));
        }

        $item_name = sanitize_text_field($request['item_name']);
        $item_ref = sanitize_text_field($request['item_ref']);
        $item_description = wp_kses_post($request['item_description']);
        $quantity = absint($request['quantity']);
        $vat_rate = absint($request['vat_rate']);
        $unit_price = floatval($request['unit_price']);
        $discount = absint($request['discount']);
        $total = ($request['quantity'] * $request['unit_price']);
        if ($discount) {
            $total_price = ($total - ($total * $request['discount'] / 100));
        } else {
            $total_price = ($request['quantity'] * $request['unit_price']);
        }

        $vat_rate_total = ($total_price * $vat_rate) / 100;
        $total_amount = $vat_rate_total + $total_price;

        $result = $wpdb->update(
            ECWP_TABLE_QUOTE_ELEMENTS,
            array(
                'item_name' => wp_kses_post($item_name),
                'item_ref' => sanitize_text_field($item_ref),
                'item_description' => wp_kses_post($item_description),
                'quantity' => $quantity,
                'vat_rate' => $vat_rate,
                'total_price' => $total_price,
                'total_amount' => $total_amount,
                'unit_price' => $unit_price,
                'discount' => $discount,
            ),
            array('id' => $item_id)
        );

        if ($result === false) {
            return new WP_REST_Response(array('success' => false, 'message' => __('Failed to edit Item', 'my-easy-compta')), 500);
        }

        $quote_id = $wpdb->get_var($wpdb->prepare("SELECT quote_id FROM %i WHERE id = %d", ECWP_TABLE_QUOTE_ELEMENTS, $item_id));
        if (!$quote_id) {
            return new \WP_Error('no_quote_found', __('No quote found for the given item.', 'my-easy-compta'), array('status' => 404));
        }
        $calculate_amount = $this->calculate_total_amount($quote_id);

        $result_quote = $wpdb->update(
            ECWP_TABLE_QUOTES,
            $calculate_amount,
            array('id' => $quote_id),
            array(
                '%f',
                '%d',
                '%f',
            ),
            array('%d')
        );

        return new WP_REST_Response(array('success' => true, 'message' => __('Item edited successfully', 'my-easy-compta')), 200);
    }

    public function delete_quote_item(WP_REST_Request $request)
    {
        global $wpdb;
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        $valid_nonce = wp_verify_nonce($nonce, 'wp_rest');
        $has_permission = current_user_can('manage_options');

        if (!$valid_nonce) {
            return new WP_Error('rest_nonce_invalid', __('Nonce invalide', 'my-easy-compta'), array('status' => 403));
        }

        if (!$has_permission) {
            return new WP_Error('rest_forbidden', __('Error API access', 'my-easy-compta'), array('status' => 403));
        }

        $item_id = absint($request->get_param('id'));

        $quote_id = $wpdb->get_var($wpdb->prepare("SELECT quote_id FROM %i WHERE id = %d", ECWP_TABLE_QUOTE_ELEMENTS, $item_id));
        if (!$quote_id) {
            return new WP_Error('no_quote_found', __('No quote found for the given item.', 'my-easy-compta'), array('status' => 404));
        }

        global $wpdb;
        $result = $wpdb->delete(ECWP_TABLE_QUOTE_ELEMENTS, array('id' => $item_id));

        $calculate_amount = $this->calculate_total_amount($quote_id);

        $result_quote = $wpdb->update(
            ECWP_TABLE_QUOTES,
            $calculate_amount,
            array('id' => $quote_id),
            array(
                '%f',
                '%d',
                '%f',
            ),
            array('%d')
        );

        return rest_ensure_response(array('success' => true, 'message' => __('Quote item deleted.', 'my-easy-compta')));
    }

    public function update_quote_items_order(WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        $valid_nonce = wp_verify_nonce($nonce, 'wp_rest');
        $has_permission = current_user_can('manage_options');

        if (!$valid_nonce || !$has_permission) {
            return new WP_Error('unauthorized', __('Unauthorized request', 'my-easy-compta'), array('status' => 401));
        }

        $order = $request->get_param('order');

        if (!is_array($order)) {
            return new WP_Error('invalid_order', __('Invalid order data', 'my-easy-compta'), array('status' => 400));
        }

        global $wpdb;
        foreach ($order as $index => $item_id) {
            $wpdb->update(
                ECWP_TABLE_QUOTE_ELEMENTS,
                array('item_order' => $index),
                array('id' => $item_id)
            );
        }

        return new WP_REST_Response(array('success' => true, 'message' => __('Quote items order updated successfully', 'my-easy-compta')), 200);
    }

    public function update_quote_status(WP_REST_Request $request)
    {
        $id = $request->get_param('id');
        $status = $request->get_param('status');
        global $wpdb;

        // Validate the status
        if (!in_array($status, ['pending', 'approved', 'rejected'])) {
            return new WP_Error('invalid_status', 'Invalid status provided', array('status' => 400));
        }

        // Validate the ID
        if (!is_numeric($id)) {
            return new WP_Error('invalid_id', 'Invalid ID provided', array('status' => 400));
        }

        $result = $wpdb->update(
            ECWP_TABLE_QUOTES,
            array('status' => $status),
            array('id' => $id),
            array('%s'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('update_failed', __('Failed to update quote status', 'my-easy-compta'), array('status' => 500));
        }

        return rest_ensure_response(array('success' => true, 'message' => __('Quote status updated successfully', 'my-easy-compta')));
    }

    function convert_quote_to_invoice($request)
    {
        global $wpdb;
        $quote_id = $request['id'];
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        $quote = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM %i WHERE id = %d", ECWP_TABLE_QUOTES, $quote_id), ARRAY_A);

        if (!$quote) {
            return new WP_Error('quote_not_found', __('Quote not found', 'my-easy-compta'), array('status' => 404));
        }

        $last_invoice_id = $wpdb->get_var($wpdb->prepare("SELECT MAX(number) AS last_id FROM %i", ECWP_TABLE_INVOICES));
        $padded_invoice_id = str_pad(intval($last_invoice_id + 1), 4, "0", STR_PAD_LEFT);

        $settings = new ECWP_Settings();
        $invoice_prefix = $settings->get_setting('invoice_prefix');

        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt;

        $invoice_data = array(
            'number' => $last_invoice_id + 1,
            'invoice_number' => $encrypt->encrypt($invoice_prefix . '_' . $padded_invoice_id),
            'client_id' => $quote['client_id'],
            'amount' => $encrypt->encrypt($quote['amount']),
            'total_amount' => $encrypt->encrypt($quote['total_amount']),
            'due_date' => $quote['due_date'],
            'status' => $encrypt->encrypt('unpaid'),
            'created_at' => $quote['created_at'],
        );

        $wpdb->insert(ECWP_TABLE_INVOICES, $invoice_data);
        $invoice_id = $wpdb->insert_id;

        if (!$invoice_id) {
            return new WP_Error('invoice_creation_failed', __('Failed to create invoice', 'my-easy-compta'), array('status' => 500));
        }

        $quote_items = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM %i WHERE quote_id = %d", ECWP_TABLE_QUOTE_ELEMENTS, $quote_id), ARRAY_A);

        foreach ($quote_items as $item) {
            $item_data = array(
                'invoice_id' => $invoice_id,
                'item_name' => $encrypt->encrypt($item['item_name']),
                'item_ref' => $encrypt->encrypt($item['item_ref']),
                'item_description' => $item['item_description'],
                'quantity' => $encrypt->encrypt($item['quantity']),
                'vat_rate' => $encrypt->encrypt($item['vat_rate']),
                'unit_price' => $encrypt->encrypt($item['unit_price']),
                'discount' => $encrypt->encrypt($item['discount']),
                'total_price' => $encrypt->encrypt($item['total_price']),
                'total_amount' => $encrypt->encrypt($item['total_amount']),
                'item_order' => $item['item_order'],
            );
            $wpdb->insert(ECWP_TABLE_INVOICE_ELEMENTS, $item_data);
        }

        $wpdb->update(
            ECWP_TABLE_QUOTES,
            array(
                'status' => 'approved',
                'converted' => 1,
            ),
            array('id' => $quote_id),
            array(
                '%s',
                '%d',
            ),
            array('%d')
        );

        return new WP_REST_Response(array('success' => true, 'message' => __('Quote converted to invoice successfully', 'my-easy-compta'), 'id' => $invoice_id), 200);
    }

    public function calculate_total_amount($quote_id)
    {
        global $wpdb;
        $amount = $wpdb->get_var($wpdb->prepare("SELECT SUM(total_price) FROM %i WHERE quote_id = %d", ECWP_TABLE_QUOTE_ELEMENTS, $quote_id));
        $totalAmount = $wpdb->get_var($wpdb->prepare("SELECT SUM(total_amount) FROM %i WHERE quote_id = %d", ECWP_TABLE_QUOTE_ELEMENTS, $quote_id));

        $data = array(
            'amount' => $amount,
            'total_amount' => $totalAmount,
        );

        return $data;
    }

    public function generate_quote_pdf(WP_REST_Request $request)
    {
        global $wpdb;
        $quote_id = $request->get_param('id');

        $pdfGenerator = new PDFGenerator($wpdb);
        $pdfGenerator->generateQuotePDF($quote_id);
    }

}
