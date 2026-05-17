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
        // Plus de sous-menu WordPress - navigation SPA uniquement
        $this->routes = new Routes();
        $this->register_api_routes();
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

        $this->routes->add_route('/quotes/(?P<id>\d+)/duplicate', 'POST', $this, 'duplicate_quote', function () {
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

        $this->routes->add_route('/quotes/pdf/(?P<id>\d+)', 'GET', $this, 'generate_quote_pdf', function ($request) {
            // Vérifier le nonce depuis le paramètre GET
            $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field($_GET['_wpnonce']) : '';
            if (!empty($nonce) && wp_verify_nonce($nonce, 'wp_rest')) {
                return current_user_can('manage_options');
            }
            return false;
        });

        // Alias version RESTful
        $this->routes->add_route('/quotes/(?P<id>\d+)/pdf', 'GET', $this, 'generate_quote_pdf', function ($request) {
            $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field($_GET['_wpnonce']) : '';
            if (!empty($nonce) && wp_verify_nonce($nonce, 'wp_rest')) {
                return current_user_can('manage_options');
            }
            return false;
        });

        $this->routes->add_route('/quotes/bulk', 'POST', $this, 'bulk_action', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/quotes/(?P<id>\d+)/notes', 'POST', $this, 'save_quote_notes', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/quotes/templates', 'GET', $this, 'get_quote_templates', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/quotes/(?P<id>\d+)/save-as-template', 'POST', $this, 'save_quote_as_template', function () {
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

        $where_clauses = ['(quotes.is_template IS NULL OR quotes.is_template = 0)'];
        $query_params = [];

        // Recherche dans le numéro de devis OU le nom du client
        if (!empty($request['quote_number'])) {
            $where_clauses[] = '(quotes.quote_number LIKE %s OR clients.company_name LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($request['quote_number']) . '%';
            $query_params[] = $search_term;
            $query_params[] = $search_term;
        }

        if (!empty($request['client'])) {
            $where_clauses[] = 'clients.company_name LIKE %s';
            $query_params[] = '%' . $wpdb->esc_like($request['client']) . '%';
        }
        if (!empty($request['status'])) {
            $where_clauses[] = 'quotes.status = %s';
            $query_params[] = $request['status'];
        }
        if (!empty($request['total_amount'])) {
            $where_clauses[] = 'quotes.total_amount = %s';
            $query_params[] = $request['total_amount'];
        }
        if (!empty($request['due_date'])) {
            $where_clauses[] = 'DATE(quotes.due_date) = %s';
            $query_params[] = $request['due_date'];
        }
        if (!empty($request['created_at'])) {
            $where_clauses[] = 'DATE(quotes.created_at) = %s';
            $query_params[] = $request['created_at'];
        }

        // Date range filter on created_at
        if (!empty($request['date_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $request['date_from'])) {
            $where_clauses[] = 'quotes.created_at >= %s';
            $query_params[]  = sanitize_text_field($request['date_from']) . ' 00:00:00';
        }
        if (!empty($request['date_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $request['date_to'])) {
            $where_clauses[] = 'quotes.created_at <= %s';
            $query_params[]  = sanitize_text_field($request['date_to']) . ' 23:59:59';
        }

        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }

        $quotes_table = ECWP_TABLE_QUOTES;
        $clients_table = ECWP_TABLE_CLIENTS;
        $currencies_table = ECWP_TABLE_CURRENCY;

        $query = "SELECT quotes.id,
                     clients.company_name,
                     currencies.symbol AS currency_symbol,
                     quotes.quote_number,
                     quotes.total_amount,
                     quotes.status,
                     quotes.due_date,
                     quotes.provisional_start_date,
                     quotes.created_at,
                     quotes.converted
              FROM {$quotes_table} AS quotes
              LEFT JOIN {$clients_table} AS clients ON quotes.client_id = clients.id
              LEFT JOIN {$currencies_table} AS currencies ON clients.currency_id = currencies.id
              $where_sql
              ORDER BY quotes.id DESC
              LIMIT %d OFFSET %d";

        $query_params[] = $per_page;
        $query_params[] = $offset;

        $quotes = $wpdb->get_results(
            $wpdb->prepare($query, ...$query_params),
            OBJECT
        );

        if ($wpdb->last_error) {
            return new \WP_Error('db_error', $wpdb->last_error, array('status' => 500));
        }

        // Créer un array de paramètres pour le count (sans offset et limit)
        $count_params = array_slice($query_params, 0, -2);

        $count_query = "SELECT COUNT(quotes.id)
                    FROM {$quotes_table} AS quotes
                    LEFT JOIN {$clients_table} AS clients ON quotes.client_id = clients.id
                    $where_sql";

        $total_count = !empty($count_params) ? $wpdb->get_var($wpdb->prepare($count_query, ...$count_params)) : $wpdb->get_var($count_query);
        $total_pages = ceil($total_count / $per_page);

        if (empty($quotes)) {
            return rest_ensure_response([
                'quotes' => [],
                'total_count' => 0,
                'total_pages' => 0,
                'page' => $page,
                'per_page' => $per_page,
            ]);
        }

        $settings_manager = new \ECWP\Admin\Settings\ECWP_Settings();
        $format_date_response = $settings_manager->get_format_date();
        $format_date = isset($format_date_response->data) ? $format_date_response->data : 'Y-m-d';

        $data = [];
        foreach ($quotes as $quote) {
            $data[] = [
                'id' => $quote->id,
                'client_name' => $quote->company_name ?: 'Client Inconnu',
                'client_currency' => $quote->currency_symbol ?: '€',
                'quote_number' => $quote->quote_number,
                'total_amount' => $quote->total_amount,
                'status' => $quote->status,
                'due_date' => $quote->due_date ? date_i18n($format_date, strtotime($quote->due_date)) : '-',
                'due_date_raw' => $quote->due_date,
                'provisional_start_date' => $quote->provisional_start_date ? date_i18n($format_date, strtotime($quote->provisional_start_date)) : '-',
                'provisional_start_date_raw' => $quote->provisional_start_date,
                'created' => $quote->created_at ? date_i18n($format_date, strtotime($quote->created_at)) : '-',
                'created_raw' => $quote->created_at,
                'converted' => $quote->converted,
            ];
        }

        return rest_ensure_response([
            'quotes' => $data,
            'total_count' => intval($total_count),
            'total_pages' => intval($total_pages),
            'page' => $page,
            'per_page' => $per_page,
        ]);
    }

    public function get_quote_details($request)
    {
        global $wpdb;
        $params = $request->get_params();
        $quote_id = absint($params['id']);
        $quotes_table = ECWP_TABLE_QUOTES;
        $clients_table = ECWP_TABLE_CLIENTS;

        // Récupérer les détails du devis
        $quote_details = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$quotes_table} WHERE id = %d", $quote_id),
            ARRAY_A
        );

        if (!$quote_details) {
            return new WP_Error('quote_not_found', __('Quote not found.', 'my-easy-compta'), array('status' => 404));
        }

        // Récupérer les informations du client
        $client = null;
        if (!empty($quote_details['client_id'])) {
            $client = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$clients_table} WHERE id = %d", $quote_details['client_id']),
                ARRAY_A
            );
        }

        return rest_ensure_response([
            'quote' => $quote_details,
            'client' => $client
        ]);
    }

    public function add_quotes($request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        $valid_nonce = wp_verify_nonce($nonce, 'wp_rest');
        $has_permission = current_user_can('manage_options');

        if (!$valid_nonce) {
            // Nonce invalid – no sensitive data logged.
            return new WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        if (!$has_permission) {
            // Permission denied.
            return new WP_Error('rest_forbidden', __('API access error', 'my-easy-compta'), array('status' => 403));
        }
        global $wpdb;
        $quotes_table = ECWP_TABLE_QUOTES;
        $settings_table = ECWP_TABLE_SETTINGS;

        $wpdb->query('START TRANSACTION');

        // Lock to prevent concurrent requests getting the same number.
        $max_number    = $wpdb->get_var("SELECT MAX(number) FROM {$quotes_table} FOR UPDATE");
        if ($max_number === null) {
            $configured_first = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$settings_table} WHERE meta_key = %s", 'quote_first'));
            $last_quote_id    = max(1, (int) $configured_first);
        } else {
            $last_quote_id = (int) $max_number + 1;
        }

        $quote_prefix = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$settings_table} WHERE meta_key = %s", 'quote_prefix'));
        $quote_prefix = $quote_prefix ? sanitize_text_field($quote_prefix) : 'EST';
        $quote_number_format = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$settings_table} WHERE meta_key = %s", 'quote_number_format')) ?: 'prefix';
        $quote_number = $this->generate_document_number($quote_prefix, $quote_number_format, $quotes_table, $last_quote_id);

        $quote_data = array(
            'number' => $last_quote_id,
            'quote_number' => $quote_number,
            'due_date' => sanitize_text_field($request['due_date']),
            'provisional_start_date' => sanitize_text_field($request['provisional_start_date']),
            'client_id' => absint($request['client_id']),
            'status' => sanitize_text_field($request['status']),
            'created_at' => current_time('Y-m-d'),
        );

        $result = $wpdb->insert(ECWP_TABLE_QUOTES, $quote_data);

        if ($result === false) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('database_insert_error', __('Could not insert quote into database', 'my-easy-compta'), array('status' => 500));
        }

        $wpdb->query('COMMIT');

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
            // Nonce invalid – no sensitive data logged.
            return new WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        if (!$has_permission) {
            // Permission denied.
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
        $quote_elements_table = ECWP_TABLE_QUOTE_ELEMENTS;
        $data = [
            'quote_id' => sanitize_text_field($params['quote_id']),
            'item_name' => sanitize_text_field($params['item_name']),
            'item_ref' => sanitize_text_field($params['item_ref']),
            'item_category' => sanitize_text_field($params['item_category']),
            'item_description' => sanitize_textarea_field($params['item_description'] ?? ''),
            'quantity' => sanitize_text_field($params['quantity']),
            'vat_rate' => intval($params['vat_rate']),
            'unit_price' => floatval($params['unit_price']),
            'discount' => intval($params['discount']),
            'total_price' => floatval($params['total_price']),
            'total_amount' => floatval($params['total_amount']),
            'is_optional' => absint($params['is_optional'] ?? 0),
            'item_order' => (int) ($wpdb->get_var($wpdb->prepare("SELECT MAX(item_order) FROM {$quote_elements_table} WHERE quote_id = %d", absint($params['quote_id']))) + 1),
        ];

        $result = $wpdb->insert(ECWP_TABLE_QUOTE_ELEMENTS, $data);

        $articles_table = ECWP_TABLE_ARTICLES;
        $existing_article = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$articles_table} WHERE name = %s", $params['item_name']));

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
                '%f',
            ),
            array('%d')
        );

        if ($result !== false && $result_quote !== false) {
            return new WP_REST_Response(array('success' => true, 'message' => __('Quote item successfully added', 'my-easy-compta')), 200);
        } else {
            return new WP_REST_Response(array('success' => false, 'message' => __('Failed to add quote item', 'my-easy-compta')), 500);
        }
    }

    public function get_quote_items($request)
    {
        global $wpdb;
        $quote_id = absint($request['id']);
        if ($quote_id <= 0) {
            return new WP_Error('invalid_quote_id', 'Invalid quote ID.', array('status' => 400));
        }

        if (!current_user_can('manage_options')) {
            return new WP_Error('unauthorized_access', 'You are not authorized to access this resource.', array('status' => 403));
        }

        $params = $request->get_params();
        $quote_id = absint($params['id']);
        $quote_elements_table = ECWP_TABLE_QUOTE_ELEMENTS;
        $articles_categories_table = ECWP_TABLE_ARTICLES_CATEGORIES;

        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
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
                ie.item_order,
                ie.is_optional
            FROM
                {$quote_elements_table} ie
            LEFT JOIN
                {$articles_categories_table} ac
            ON
                ie.item_category = ac.id
            WHERE
                ie.quote_id = %d
            ORDER BY
                ie.item_order ASC",
                $quote_id
            ),
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
        $item_id = absint($params['id']);

        $quote_elements_table = ECWP_TABLE_QUOTE_ELEMENTS;
        $item_details = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, item_name, item_ref, item_description, quantity, vat_rate, unit_price, discount, total_price, total_amount, item_order, is_optional FROM {$quote_elements_table} WHERE id = %d ORDER BY item_order ASC",
                $item_id
            ),
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

    public function bulk_action(WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        $action  = sanitize_key($request->get_param('action'));
        $raw_ids = $request->get_param('ids');

        if ($action !== 'delete') {
            return new WP_Error('invalid_action', __('Invalid bulk action.', 'my-easy-compta'), array('status' => 400));
        }
        if (!is_array($raw_ids) || empty($raw_ids)) {
            return new WP_Error('invalid_ids', __('No IDs provided.', 'my-easy-compta'), array('status' => 400));
        }

        $ids  = array_filter(array_map('absint', $raw_ids));
        if (empty($ids)) {
            return new WP_Error('invalid_ids', __('No valid IDs provided.', 'my-easy-compta'), array('status' => 400));
        }

        global $wpdb;
        $done    = 0;
        $skipped = 0;

        $wpdb->query('START TRANSACTION');
        foreach ($ids as $id) {
            $del_items = $wpdb->delete(ECWP_TABLE_QUOTE_ELEMENTS, array('quote_id' => $id));
            $del_quote = $wpdb->delete(ECWP_TABLE_QUOTES, array('id' => $id));
            if ($del_quote !== false) {
                $done++;
            } else {
                $skipped++;
            }
        }
        $wpdb->query('COMMIT');

        return rest_ensure_response(array(
            'success' => true,
            'deleted' => $done,
            'skipped' => $skipped,
            'message' => sprintf(_n('%d quote deleted.', '%d quotes deleted.', $done, 'my-easy-compta'), $done),
        ));
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
        $quotes_table = ECWP_TABLE_QUOTES;
        $original_quote = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$quotes_table} WHERE id = %d", $quote_id), ARRAY_A);

        if (!$original_quote) {
            return new WP_Error('quote_not_found', __('Quote not found.', 'easy-compta'), array('status' => 404));
        }

        unset($original_quote['id']);
        $quotes_table = ECWP_TABLE_QUOTES;
        $settings_table = ECWP_TABLE_SETTINGS;
        $max_dup_q     = $wpdb->get_var("SELECT MAX(number) FROM {$quotes_table} FOR UPDATE");
        if ($max_dup_q === null) {
            $first         = (int) $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$settings_table} WHERE meta_key = %s", 'quote_first'));
            $last_quote_id = max(1, $first);
        } else {
            $last_quote_id = (int) $max_dup_q + 1;
        }

        $quote_prefix = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$settings_table} WHERE meta_key = %s", 'quote_prefix'));
        $quote_prefix = $quote_prefix ? sanitize_text_field($quote_prefix) : 'EST';
        $quote_number_format = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$settings_table} WHERE meta_key = %s", 'quote_number_format')) ?: 'prefix';
        $quote_number = $this->generate_document_number($quote_prefix, $quote_number_format, $quotes_table, $last_quote_id);

        $original_quote['number'] = $last_quote_id;
        $original_quote['quote_number'] = $quote_number;

        // Reset state fields — the duplicate starts fresh
        $original_quote['status']      = 'draft';
        $original_quote['converted']   = 0;
        $original_quote['is_template'] = 0;

        // Remove addon-specific columns that may not exist in the base table
        unset($original_quote['signed'], $original_quote['file_sign'], $original_quote['sent']);

        // Mettre à jour les dates avec la date actuelle
        $current_date = current_time('Y-m-d');
        $current_datetime = current_time('Y-m-d H:i:s');

        // Date de création = aujourd'hui
        if (isset($original_quote['created'])) {
            $original_quote['created'] = $current_date;
        }
        if (isset($original_quote['created_at'])) {
            $original_quote['created_at'] = $current_datetime;
        }
        if (isset($original_quote['date_created'])) {
            $original_quote['date_created'] = $current_datetime;
        }

        // Date de validité = aujourd'hui + 1 mois
        $due_date = wp_date('Y-m-d', strtotime(current_time('Y-m-d') . ' +1 month'));
        if (isset($original_quote['due_date'])) {
            $original_quote['due_date'] = $due_date;
        }
        if (isset($original_quote['validity_date'])) {
            $original_quote['validity_date'] = $due_date;
        }

        $insert_quote = $wpdb->insert(ECWP_TABLE_QUOTES, $original_quote);

        if ($insert_quote === false) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('quote_duplicate_failed', __('Failed to duplicate quote.', 'easy-compta'), array('status' => 500));
        }

        $new_quote_id = $wpdb->insert_id;

        $quote_elements_table = ECWP_TABLE_QUOTE_ELEMENTS;
        $original_items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$quote_elements_table} WHERE quote_id = %d", $quote_id), ARRAY_A);

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

        return new \WP_REST_Response(array('success' => true, 'message' => __('Devis dupliqué avec succès', 'my-easy-compta'), 'new_quote_id' => $new_quote_id), 200);
    }

    public function edit_quote(WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        $valid_nonce = wp_verify_nonce($nonce, 'wp_rest');
        $has_permission = current_user_can('manage_options');

        if (!$valid_nonce) {
            // Nonce invalid – no sensitive data logged.
            return new WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        if (!$has_permission) {
            // Permission denied.
            return new WP_Error('rest_forbidden', __('API access error', 'my-easy-compta'), array('status' => 403));
        }

        $quote_id = absint($request->get_param('id'));

        if ($quote_id <= 0) {
            return new WP_Error('invalid_quote_id', 'Invalid quote ID', array('status' => 400));
        }

        $params = $request->get_params();

        $quote_date = sanitize_text_field($params['due_date']);
        $provisional_start_date = sanitize_text_field($params['provisional_start_date']);
        $client_id = absint($params['client_id']);
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
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        global $wpdb;
        $item_id = absint($request['id']);
        if ($item_id <= 0) {
            return new WP_Error('invalid_Item_id', 'ID client invalid.', array('status' => 400));
        }

        $item_name        = sanitize_text_field($request['item_name']);
        $item_ref         = sanitize_text_field($request['item_ref']);
        $item_description = wp_kses_post($request['item_description']);
        $quantity         = floatval($request['quantity']);
        $vat_rate         = absint($request['vat_rate']);
        $unit_price       = floatval($request['unit_price']);
        $discount         = absint($request['discount']);
        $total = $quantity * $unit_price;
        if ($discount) {
            $total_price = $total - ($total * $discount / 100);
        } else {
            $total_price = $total;
        }

        $vat_rate_total = ($total_price * $vat_rate) / 100;
        $total_amount = $vat_rate_total + $total_price;

        $is_optional = absint($request['is_optional'] ?? 0);

        $result = $wpdb->update(
            ECWP_TABLE_QUOTE_ELEMENTS,
            array(
                'item_name'        => $item_name,
                'item_ref'         => $item_ref,
                'item_description' => $item_description,
                'quantity'         => $quantity,
                'vat_rate'         => $vat_rate,
                'total_price'      => $total_price,
                'total_amount'     => $total_amount,
                'unit_price'       => $unit_price,
                'discount'         => $discount,
                'is_optional'      => $is_optional,
            ),
            array('id' => $item_id),
            array('%s', '%s', '%s', '%f', '%d', '%f', '%f', '%f', '%d', '%d'),
            array('%d')
        );

        if ($result === false) {
            return new WP_REST_Response(array('success' => false, 'message' => __('Failed to edit Item', 'my-easy-compta')), 500);
        }

        $quote_elements_table = ECWP_TABLE_QUOTE_ELEMENTS;
        $quote_id = $wpdb->get_var($wpdb->prepare("SELECT quote_id FROM {$quote_elements_table} WHERE id = %d", $item_id));
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

        $quote_elements_table = ECWP_TABLE_QUOTE_ELEMENTS;
        $quote_id = $wpdb->get_var($wpdb->prepare("SELECT quote_id FROM {$quote_elements_table} WHERE id = %d", $item_id));
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
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        $id = $request->get_param('id');
        $status = $request->get_param('status');
        global $wpdb;

        // Validate the status
        if (!in_array($status, ['draft', 'pending', 'approved', 'rejected'])) {
            return new WP_Error('invalid_status', 'Invalid status provided', array('status' => 400));
        }

        // Validate the ID
        if (!is_numeric($id)) {
            return new WP_Error('invalid_id', 'Invalid ID provided', array('status' => 400));
        }

        $quotes_table = ECWP_TABLE_QUOTES;
        $result = $wpdb->update(
            $quotes_table,
            array('status' => $status),
            array('id' => $id),
            array('%s'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('update_failed', __('Failed to update quote status', 'my-easy-compta'), array('status' => 500));
        }

        if (in_array($status, ['approved', 'rejected'], true)) {
            do_action('ecwp_quote_status_changed', absint($id), $status);
        }

        $total_amount = $wpdb->get_var(
            $wpdb->prepare("SELECT total_amount FROM {$quotes_table} WHERE id = %d", $id)
        );

        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Quote status updated successfully', 'my-easy-compta'),
            'total_amount' => $total_amount,
        ));
    }

    function convert_quote_to_invoice($request)
    {
        global $wpdb;
        $quote_id = absint($request['id']);
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        $quotes_table = ECWP_TABLE_QUOTES;
        $quote = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$quotes_table} WHERE id = %d", $quote_id),
            ARRAY_A
        );

        if (!$quote) {
            return new WP_Error('quote_not_found', __('Quote not found', 'my-easy-compta'), array('status' => 404));
        }

        // Guard: prevent converting an already-converted quote.
        if (!empty($quote['converted']) && (int) $quote['converted'] === 1) {
            return new WP_Error('quote_already_converted', __('Ce devis a déjà été converti en facture.', 'my-easy-compta'), array('status' => 400));
        }

        $wpdb->query('START TRANSACTION');

        $invoices_table  = ECWP_TABLE_INVOICES;
        $settings_table  = ECWP_TABLE_SETTINGS;
        // Lock to prevent concurrent invoice number conflicts.
        $max_inv = $wpdb->get_var("SELECT MAX(number) FROM {$invoices_table} FOR UPDATE");
        if ($max_inv === null) {
            $configured_first = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$settings_table} WHERE meta_key = %s", 'invoice_first'));
            $next_invoice_seq = max(1, (int) $configured_first);
        } else {
            $next_invoice_seq = (int) $max_inv + 1;
        }

        $settings = new ECWP_Settings();
        $invoice_prefix        = $settings->get_setting('invoice_prefix') ?: 'INV';
        $invoice_number_format = $settings->get_setting('invoice_number_format') ?: 'prefix';
        $invoice_number_str    = $this->generate_document_number($invoice_prefix, $invoice_number_format, $invoices_table, $next_invoice_seq);

        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt;

        $invoice_data = array(
            'number' => $next_invoice_seq,
            'invoice_number' => $encrypt->encrypt($invoice_number_str),
            'client_id' => $quote['client_id'],
            'amount' => $encrypt->encrypt($quote['amount']),
            'total_amount' => $encrypt->encrypt($quote['total_amount']),
            'due_date' => $quote['due_date'],
            'status' => $encrypt->encrypt('unpaid'),
            'status_stats' => 'unpaid',
            'created_at' => $quote['created_at'],
        );

        $wpdb->insert(ECWP_TABLE_INVOICES, $invoice_data);
        $invoice_id = $wpdb->insert_id;

        if (!$invoice_id) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('invoice_creation_failed', __('Failed to create invoice', 'my-easy-compta'), array('status' => 500));
        }

        $quote_elements_table = ECWP_TABLE_QUOTE_ELEMENTS;
        $quote_items = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$quote_elements_table} WHERE quote_id = %d", $quote_id),
            ARRAY_A
        );

        foreach ($quote_items as $item) {
            $item_data = array(
                'invoice_id'       => $invoice_id,
                'item_name'        => $encrypt->encrypt($item['item_name']),
                'item_ref'         => $encrypt->encrypt($item['item_ref']),
                'item_description' => $encrypt->encrypt($item['item_description']),
                'item_category'    => $item['item_category'],
                'quantity'         => $encrypt->encrypt($item['quantity']),
                'vat_rate'         => $encrypt->encrypt($item['vat_rate']),
                'unit_price'       => $encrypt->encrypt($item['unit_price']),
                'discount'         => $encrypt->encrypt($item['discount']),
                'total_price'      => $encrypt->encrypt($item['total_price']),
                'total_amount'     => $encrypt->encrypt($item['total_amount']),
                'item_order'       => $item['item_order'],
            );
            $inserted_item = $wpdb->insert(ECWP_TABLE_INVOICE_ELEMENTS, $item_data);
            if ($inserted_item === false) {
                $wpdb->query('ROLLBACK');
                return new WP_Error(
                    'item_copy_failed',
                    __('Erreur lors de la copie des éléments du devis vers la facture.', 'my-easy-compta'),
                    array('status' => 500)
                );
            }
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

        $wpdb->query('COMMIT');

        do_action('ecwp_quote_converted', $quote_id, $invoice_id);

        return new WP_REST_Response(array('success' => true, 'message' => __('Quote converted to invoice successfully', 'my-easy-compta'), 'id' => $invoice_id), 200);
    }

    public function calculate_total_amount($quote_id)
    {
        global $wpdb;
        $quote_elements_table = ECWP_TABLE_QUOTE_ELEMENTS;
        $amount = $wpdb->get_var($wpdb->prepare("SELECT SUM(total_price) FROM {$quote_elements_table} WHERE quote_id = %d", $quote_id));
        $totalAmount = $wpdb->get_var($wpdb->prepare("SELECT SUM(total_amount) FROM {$quote_elements_table} WHERE quote_id = %d", $quote_id));

        $data = array(
            'amount' => $amount,
            'total_amount' => $totalAmount,
        );

        return $data;
    }

    public function generate_quote_pdf(WP_REST_Request $request)
    {
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            return new WP_Error('rest_forbidden', __('Désolé, vous n\'avez pas l\'autorisation de faire cela.', 'my-easy-compta'), array('status' => 401));
        }

        global $wpdb;
        $quote_id = $request->get_param('id');

        if (!is_numeric($quote_id)) {
            return new WP_Error('invalid_quote_id', __('Invalid quote ID.', 'my-easy-compta'), array('status' => 400));
        }

        $pdfGenerator = new PDFGenerator($wpdb);
        $pdfGenerator->generateQuotePDF($quote_id);
        exit; // Important pour éviter d'ajouter du contenu supplémentaire
    }

    private function generate_document_number(string $prefix, string $format, string $table, int $seq): string
    {
        $year  = (int) current_time('Y');
        $month = (int) current_time('m');
        $num   = str_pad($seq, 4, '0', STR_PAD_LEFT);

        switch ($format) {
            case 'prefix_year':
                return $prefix . '-' . $year . '-' . $num;
            case 'prefix_year_month':
                return $prefix . '-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . $num;
            case 'year':
                return $year . '-' . $num;
            default: // 'prefix'
                return $prefix . '-' . $num;
        }
    }

    /**
     * Save internal notes on a quote (no lock check — notes are always editable).
     */
    public function save_quote_notes(WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('invalid_nonce', __('Nonce verification failed.', 'my-easy-compta'), array('status' => 403));
        }

        global $wpdb;
        $quote_id       = absint($request->get_param('id'));
        $internal_notes = sanitize_textarea_field($request->get_param('internal_notes') ?? '');

        $updated = $wpdb->update(
            ECWP_TABLE_QUOTES,
            array('internal_notes' => $internal_notes),
            array('id'             => $quote_id),
            array('%s'),
            array('%d')
        );

        if ($updated === false) {
            return new WP_Error('db_update_error', __('Failed to save notes.', 'my-easy-compta'), array('status' => 500));
        }

        return new WP_REST_Response(array('success' => true), 200);
    }

    /**
     * Return all quote templates (is_template = 1).
     */
    public function get_quote_templates(WP_REST_Request $request)
    {
        global $wpdb;
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        $table   = ECWP_TABLE_QUOTES;

        $rows = $wpdb->get_results(
            "SELECT id, quote_number, created_at FROM {$table} WHERE is_template = 1 ORDER BY created_at DESC",
            ARRAY_A
        );

        $templates = array_map(function ($r) use ($encrypt) {
            $name = $encrypt->decrypt($r['quote_number']);
            return [
                'id'   => (int) $r['id'],
                'name' => $name ?: 'Modèle #' . $r['id'],
                'date' => $r['created_at'],
            ];
        }, $rows ?: []);

        return rest_ensure_response(array('templates' => $templates));
    }

    /**
     * Toggle is_template flag on a quote.
     */
    public function save_quote_as_template(WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('invalid_nonce', __('Nonce verification failed.', 'my-easy-compta'), array('status' => 403));
        }

        global $wpdb;
        $quote_id    = absint($request->get_param('id'));
        $is_template = (int) (bool) $request->get_param('is_template');
        $table       = ECWP_TABLE_QUOTES;

        $updated = $wpdb->update(
            $table,
            array('is_template' => $is_template),
            array('id'          => $quote_id),
            array('%d'),
            array('%d')
        );

        if ($updated === false) {
            return new WP_Error('db_update_error', __('Failed to update template status.', 'my-easy-compta'), array('status' => 500));
        }

        return new WP_REST_Response(array('success' => true, 'is_template' => $is_template), 200);
    }

}
