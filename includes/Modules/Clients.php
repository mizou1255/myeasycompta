<?php

namespace ECWP\Admin;

use ECWP\Admin\Settings\ECWP_Settings;
use ECWP\API\Routes;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class ECWP_Clients
{
    protected $routes;

    public function __construct()
    {
        // Plus de sous-menu WordPress - navigation SPA uniquement
        $this->routes = new Routes();
        $this->register_api_routes();
    }

    /**
     * @return [type]
     */
    private function register_api_routes()
    {
        $this->routes->add_route('/clients', 'GET', $this, 'get_clients', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/list-clients', 'GET', $this, 'get_list_clients', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/clients/details/(?P<id>\d+)', 'GET', $this, 'get_client_details', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/clients/add', 'POST', $this, 'add_client', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/clients/(?P<id>\d+)', 'PUT', $this, 'update_client', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/clients/(?P<id>\d+)/archive', 'POST', $this, 'archive_client', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/clients/(?P<id>\d+)/statement', 'GET', $this, 'generate_client_statement', function ($request) {
            $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field($_GET['_wpnonce']) : '';
            if (!empty($nonce) && wp_verify_nonce($nonce, 'wp_rest')) {
                return current_user_can('manage_options');
            }
            return false;
        });

        $this->routes->add_route('/clients/(?P<id>\d+)', 'DELETE', $this, 'delete_client', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/options', 'GET', $this, 'get_Easy_Compta_options', function () {
            return current_user_can('manage_options');
        });

        $this->routes->register_routes();
    }

    /**
     * @param mixed $request
     *
     * @return [type]
     */
    public function get_clients($request)
    {
        global $wpdb;
        $per_page = isset($request['per_page']) ? intval($request['per_page']) : 10;
        $page = isset($request['page']) ? intval($request['page']) : 1;
        $offset = ($page - 1) * $per_page;

        $where_clauses = [];
        $query_params = [];

        // By default show only active clients; pass show_archived=1 to include archived ones
        $show_archived = !empty($request['show_archived']) && intval($request['show_archived']) === 1;
        if (!$show_archived) {
            $where_clauses[] = '(archived IS NULL OR archived = 0)';
        }

        if (!empty($request['company_name'])) {
            $where_clauses[] = 'company_name LIKE %s';
            $query_params[] = '%' . $wpdb->esc_like($request['company_name']) . '%';
        }
        if (!empty($request['manager_name'])) {
            $where_clauses[] = 'manager_name LIKE %s';
            $query_params[] = '%' . $wpdb->esc_like($request['manager_name']) . '%';
        }
        if (!empty($request['email'])) {
            $where_clauses[] = 'email LIKE %s';
            $query_params[] = '%' . $wpdb->esc_like($request['email']) . '%';
        }
        if (!empty($request['phone'])) {
            $where_clauses[] = 'phone LIKE %s';
            $query_params[] = '%' . $wpdb->esc_like($request['phone']) . '%';
        }

        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }

        $clients_table = ECWP_TABLE_CLIENTS;
        $total_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$clients_table} $where_sql", ...$query_params));
        $query_params[] = $per_page;
        $query_params[] = $offset;

        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$clients_table} $where_sql ORDER BY company_name ASC LIMIT %d OFFSET %d", ...$query_params),
            OBJECT
        );

        // Cast numeric fields so JSON sends integers, not strings
        foreach ($results as $r) {
            $r->archived = (int) $r->archived;
        }

        $total_pages = ceil($total_count / $per_page);

        $response = array(
            'clients' => $results,
            'total_count' => $total_count,
            'total_pages' => $total_pages,
            'page' => $page,
            'per_page' => $per_page,
        );

        return rest_ensure_response($response);
    }

    /**
     * @param mixed $request
     *
     * @return [type]
     */
    public function get_list_clients($request)
    {
        global $wpdb;
        $clients_table = ECWP_TABLE_CLIENTS;
        $currencies_table = ECWP_TABLE_CURRENCY;
        $results = $wpdb->get_results("SELECT
        c.id,
        c.company_name,
        c.email,
        c.currency_id,
        cur.symbol as currency_symbol
    FROM
    {$clients_table} c
    LEFT JOIN
    {$currencies_table} cur ON c.currency_id = cur.id
    ORDER BY
        c.company_name ASC",
            ARRAY_A);

        $response = array(
            'clients' => $results,
        );
        return rest_ensure_response($response);
    }

    /**
     * @param mixed $request
     *
     * @return [type]
     */
    public function get_client_details($request)
    {
        global $wpdb;
        $params = $request->get_params();
        $client_id = absint($params['id']);
        $clients_table = ECWP_TABLE_CLIENTS;
        $client_details = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$clients_table} WHERE id = %d", $client_id),
            ARRAY_A
        );
        if (!$client_details) {
            return new WP_Error('client_not_found', __('Client not found.', 'my-easy-compta'), array('status' => 404));
        }

        // Financial summary
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        $invoices = $wpdb->get_results($wpdb->prepare(
            "SELECT total_amount FROM %i WHERE client_id = %d AND (is_template IS NULL OR is_template = 0)",
            ECWP_TABLE_INVOICES, $client_id
        ), ARRAY_A);

        $total_invoiced = 0;
        foreach ($invoices as $inv) {
            $total_invoiced += floatval($encrypt->decrypt($inv['total_amount']));
        }

        // Use payments table for total_paid — counts partial payments correctly
        $total_paid = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM %i WHERE client_id = %d",
            ECWP_TABLE_PAYMENTS, $client_id
        ));

        $currency_symbol = $wpdb->get_var($wpdb->prepare(
            "SELECT cur.symbol FROM %i AS cur
             INNER JOIN %i AS cli ON cli.currency_id = cur.id
             WHERE cli.id = %d LIMIT 1",
            ECWP_TABLE_CURRENCY, ECWP_TABLE_CLIENTS, $client_id
        )) ?: '€';

        $client_details['_financials'] = [
            'total_invoiced' => $total_invoiced,
            'total_paid'     => $total_paid,
            'outstanding'    => $total_invoiced - $total_paid,
            'symbol'         => $currency_symbol,
        ];

        // Payment behavior
        $total_inv_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM %i WHERE client_id = %d AND (is_template IS NULL OR is_template = 0)",
            ECWP_TABLE_INVOICES, $client_id
        ));
        $overdue_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM %i WHERE client_id = %d AND due_date < %s AND status_stats IN ('unpaid','partial') AND (is_template IS NULL OR is_template = 0)",
            ECWP_TABLE_INVOICES, $client_id, current_time('Y-m-d')
        ));

        // Average payment delay: avg days from invoice created_at to last payment date
        $avg_delay_row = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(DATEDIFF(p.payment_date, i.created_at))
             FROM %i AS p
             INNER JOIN %i AS i ON p.invoice_id = i.id
             WHERE i.client_id = %d AND p.payment_date IS NOT NULL",
            ECWP_TABLE_PAYMENTS, ECWP_TABLE_INVOICES, $client_id
        ));

        $client_details['_payment_behavior'] = [
            'total_invoices' => $total_inv_count,
            'overdue_count'  => $overdue_count,
            'avg_delay_days' => $avg_delay_row !== null ? (int) round((float) $avg_delay_row) : null,
        ];

        return rest_ensure_response($client_details);
    }

    /**
     * @param WP_REST_Request $request
     *
     * @return [type]
     */
    public function add_client(WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        $valid_nonce = wp_verify_nonce($nonce, 'wp_rest');
        $has_permission = current_user_can('manage_options');

        if (!$valid_nonce) {
            // Nonce invalid – no sensitive data logged.
            return new WP_Error('rest_nonce_invalid', __('Nonce invalide', 'my-easy-compta'), array('status' => 403));
        }

        if (!$has_permission) {
            // Permission denied.
            return new WP_Error('rest_forbidden', __('Error API access', 'my-easy-compta'), array('status' => 403));
        }

        $params = $request->get_params();
        $required_fields = ['company_name', 'email'];

        foreach ($required_fields as $field) {
            if (empty($params[$field])) {
                return new WP_Error(
                    'rest_missing_param',
                    /* translators: %s: is the field name. */
                    sprintf(
                        esc_html__('The field %s is required', 'my-easy-compta'),
                        esc_html($field)
                    ),
                    array('status' => 422)
                );
            }
        }

        $company_name = sanitize_text_field($params['company_name']);
        $email = sanitize_email($params['email']);
        if (!is_email($email)) {
            return new WP_Error(
                'rest_invalid_param',
                __('The email address is invalid', 'my-easy-compta'),
                array('status' => 422)
            );
        }

        // Check if client already exists
        global $wpdb;
        $clients_table = ECWP_TABLE_CLIENTS;
        $existing_client = $wpdb->get_row(
            $wpdb->prepare("SELECT id FROM {$clients_table} WHERE company_name = %s AND email = %s",
                $company_name, $email));

        if ($existing_client) {
            return new WP_Error('rest_client_exists', __('Client already exists', 'my-easy-compta'), array('status' => 409));
        }

        if (!empty($params['currency_id'])) {
            $currency_id = absint($params['currency_id']);
            $currencies_table = ECWP_TABLE_CURRENCY;
            $currency_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$currencies_table} WHERE id = %d", $currency_id));

            if (!$currency_exists) {
                return new WP_Error('rest_invalid_param', __('Currency ID is invalid', 'my-easy-compta'), array('status' => 422));
            }
        }

        $data = array(
            'company_name' => $company_name,
            'siren_number' => sanitize_text_field($params['siren_number'] ?? ''),
            'tax_number' => sanitize_text_field($params['tax_number'] ?? ''),
            'manager_name' => sanitize_text_field($params['manager_name'] ?? ''),
            'address' => sanitize_text_field($params['address'] ?? ''),
            'city' => sanitize_text_field($params['city'] ?? ''),
            'postal_code' => sanitize_text_field($params['postal_code'] ?? ''),
            'country' => sanitize_text_field($params['country'] ?? ''),
            'phone' => sanitize_text_field($params['phone'] ?? ''),
            'mobile_phone' => sanitize_text_field($params['mobile_phone'] ?? ''),
            'email' => $email,
            'website' => esc_url($params['website'] ?? ''),
            'currency_id' => absint($params['currency_id'] ?? 0),
            'note' => sanitize_textarea_field($params['note'] ?? ''),
        );

        if (isset($params['user_create']) && $params['user_create']) {
            $data['user_create'] = absint($params['user_create']);
        }

        if (isset($params['siret']) && $params['siret']) {
            $data['siret_number'] = sanitize_text_field($params['siret']);
        }

        $result = $wpdb->insert(ECWP_TABLE_CLIENTS, $data);

        if (false === $result) {
            // DB error – details not exposed to logs in production.
            return new WP_REST_Response(array('success' => false, 'data' => $data, 'message' => __('Failed to add client', 'my-easy-compta')), 500);
        } else {
            $inserted_id = $wpdb->insert_id;
            $data['id'] = $inserted_id;
            do_action('ecwp_add_user', $data);
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'message' => __('Client added successfully', 'my-easy-compta'),
                    'client' => array(
                        'id' => $inserted_id,
                        'company_name' => $company_name,
                        'email' => $email,
                    ),
                ),
                200
            );
        }
    }

    /**
     * @param mixed $request
     *
     * @return [type]
     */
    public function update_client($request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $client_id = isset($request['id']) ? $request['id'] : null;
        $client_id = absint($client_id);
        if (empty($client_id) || !is_numeric($client_id)) {
            return new WP_Error('invalid_client_id', 'ID client invalid.', array('status' => 400));
        }
        $params = $request->get_params();
        $allowed_fields = [
            'company_name'   => 'sanitize_text_field',
            'siren_number'   => 'sanitize_text_field',
            'tax_number'     => 'sanitize_text_field',
            'manager_name'   => 'sanitize_text_field',
            'address'        => 'sanitize_text_field',
            'city'           => 'sanitize_text_field',
            'postal_code'    => 'sanitize_text_field',
            'country'        => 'sanitize_text_field',
            'phone'          => 'sanitize_text_field',
            'mobile_phone'   => 'sanitize_text_field',
            'email'          => 'sanitize_email',
            'website'        => 'esc_url_raw',
            'note'           => 'sanitize_textarea_field',
        ];
        $data = [];
        foreach ($allowed_fields as $field => $sanitizer) {
            if (array_key_exists($field, $params)) {
                $data[$field] = call_user_func($sanitizer, $params[$field]);
            }
        }
        // Integer fields handled separately
        if (array_key_exists('currency_id', $params)) {
            $data['currency_id'] = absint($params['currency_id']);
        }
        if (empty($data)) {
            return new WP_Error('no_data', __('No valid fields provided', 'my-easy-compta'), array('status' => 400));
        }
        $result = $wpdb->update(
            ECWP_TABLE_CLIENTS,
            $data,
            array('id' => $client_id)
        );
        if ($result === false) {
            return new WP_REST_Response(array('success' => false, 'message' => __('Failed to edit client', 'my-easy-compta')), 500);
        }
        return new WP_REST_Response(array('success' => true, 'message' => __('Client edited successfully', 'my-easy-compta')), 200);
    }

    /**
     * @param mixed $request
     *
     * @return [type]
     */
    public function delete_client($request)
    {

        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }
        $client_id = absint($request['id'] ?? 0);
        if (!$client_id) {
            return new WP_Error('invalid_client_id', __('Invalid client ID.', 'my-easy-compta'), array('status' => 400));
        }
        global $wpdb;

        $wpdb->query('START TRANSACTION');

        // Cascade-delete all client data
        $quotes = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM " . ECWP_TABLE_QUOTES . " WHERE client_id = %d", $client_id
        ));
        foreach ($quotes as $quote_id) {
            $wpdb->delete(ECWP_TABLE_QUOTE_ELEMENTS, array('quote_id' => $quote_id));
            $wpdb->delete(ECWP_TABLE_QUOTES, array('id' => $quote_id));
        }

        $invoices = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM " . ECWP_TABLE_INVOICES . " WHERE client_id = %d", $client_id
        ));
        foreach ($invoices as $invoice_id) {
            $wpdb->delete(ECWP_TABLE_INVOICE_ELEMENTS, array('invoice_id' => $invoice_id));
            $wpdb->delete(ECWP_TABLE_PAYMENTS, array('invoice_id' => $invoice_id));
            $wpdb->delete(ECWP_TABLE_INVOICES, array('id' => $invoice_id));
        }

        // Credits are invoices belonging to the client too
        $wpdb->delete(ECWP_TABLE_CREDITS, array('client_id' => $client_id));

        // Remaining payments directly linked to client (e.g. without invoice)
        $wpdb->delete(ECWP_TABLE_PAYMENTS, array('client_id' => $client_id));

        $result = $wpdb->delete(ECWP_TABLE_CLIENTS, array('id' => $client_id));

        if ($result === false) {
            $wpdb->query('ROLLBACK');
            return new WP_REST_Response(array('success' => false, 'message' => __('Failed to delete client', 'my-easy-compta')), 500);
        }

        $wpdb->query('COMMIT');
        return new WP_REST_Response(array('success' => true, 'message' => __('Client deleted successfully', 'my-easy-compta')), 200);
    }

    /**
     * @param mixed $client_id
     *
     * @return [type]
     */
    private function check_associated_data($client_id)
    {
        global $wpdb;
        $quotes_table = ECWP_TABLE_QUOTES;
        $invoices_table = ECWP_TABLE_INVOICES;
        $payments_table = ECWP_TABLE_PAYMENTS;
        $quote_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$quotes_table} WHERE client_id = %d", $client_id));
        $invoice_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$invoices_table} WHERE client_id = %d", $client_id));
        $payment_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$payments_table} WHERE client_id = %d", $client_id));
        return ($quote_count > 0 || $invoice_count > 0 || $payment_count > 0);
    }

    /**
     * @return [type]
     */

    public function is_plugin_active_custom($plugin)
    {
        $plugins = get_option('active_plugins', array());
        return in_array($plugin, $plugins);
    }
    public function get_Easy_Compta_options()
    {
        global $wpdb;
        $currencies_table = ECWP_TABLE_CURRENCY;
        $currency_options = $wpdb->get_results("SELECT id, name, code, symbol FROM {$currencies_table}");
        $settings = new ECWP_Settings;

        $addon_user_active = 0;
        if ($this->is_plugin_active_custom('my-easy-compta-user/my-easy-compta-user.php')) {
            $addon_user_active = $settings->get_setting(ECWP_USER_SETTINGS_VAR);
        }

        $addon_siret_active = 0;
        if ($this->is_plugin_active_custom('my-easy-compta-siret/my-easy-compta-siret.php')) {
            $addon_siret_active = $settings->get_setting(ECWP_SIRET_SETTINGS_VAR);
        }

        $default_currency = $settings->get_setting('default_currency');

        return array(
            'currency_options' => $currency_options,
            'default_currency' => $default_currency,
            'addon_user_active' => $addon_user_active,
            'addon_siret_active' => $addon_siret_active,
        );
    }

    /**
     * Toggle archived status of a client.
     * POST /clients/{id}/archive
     */
    public function archive_client(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', __('Nonce verification failed.', 'my-easy-compta'), array('status' => 403));
        }

        global $wpdb;
        $client_id = absint($request->get_param('id'));

        $current = $wpdb->get_var($wpdb->prepare(
            "SELECT archived FROM " . ECWP_TABLE_CLIENTS . " WHERE id = %d",
            $client_id
        ));

        if ($current === null) {
            return new \WP_Error('not_found', __('Client not found.', 'my-easy-compta'), array('status' => 404));
        }

        $new_archived = $current ? 0 : 1;

        $wpdb->update(
            ECWP_TABLE_CLIENTS,
            array('archived' => $new_archived),
            array('id'       => $client_id),
            array('%d'),
            array('%d')
        );

        return new \WP_REST_Response(array(
            'success'  => true,
            'archived' => (bool) $new_archived,
        ), 200);
    }

    /**
     * Generate a client account statement PDF.
     * GET /clients/{id}/statement
     */
    public function generate_client_statement(\WP_REST_Request $request)
    {
        global $wpdb;

        require_once ECWP_PATH . '/vendor/autoload.php';

        $client_id = absint($request->get_param('id'));
        $encrypt   = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        // Fetch client
        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, curr.symbol AS currency_symbol
             FROM " . ECWP_TABLE_CLIENTS . " c
             LEFT JOIN " . ECWP_TABLE_CURRENCY . " curr ON c.currency_id = curr.id
             WHERE c.id = %d",
            $client_id
        ), ARRAY_A);

        if (!$client) {
            return new \WP_Error('not_found', __('Client not found.', 'my-easy-compta'), ['status' => 404]);
        }

        // Fetch invoices
        $invoices = $wpdb->get_results($wpdb->prepare(
            "SELECT invoice_number, status, total_amount, paid_amount, due_date, created_at
             FROM " . ECWP_TABLE_INVOICES . "
             WHERE client_id = %d
             ORDER BY created_at ASC",
            $client_id
        ), ARRAY_A);

        $symbol    = $client['currency_symbol'] ?: '€';
        $total_inv = 0;
        $total_paid_sum = 0;

        $rows_html = '';
        $status_labels = [
            'draft'   => 'Brouillon',
            'unpaid'  => 'Impayée',
            'partial' => 'Partiel',
            'paid'    => 'Payée',
        ];
        $status_colors = [
            'draft'   => '#94a3b8',
            'unpaid'  => '#ef4444',
            'partial' => '#f59e0b',
            'paid'    => '#22c55e',
        ];

        foreach ($invoices as $inv) {
            $num    = $encrypt->decrypt($inv['invoice_number']) ?: '#';
            $status = $encrypt->decrypt($inv['status']) ?: 'draft';
            $total  = floatval($encrypt->decrypt($inv['total_amount']));
            $paid   = floatval($inv['paid_amount'] ?? 0);
            $due    = floatval($total - $paid);
            $total_inv     += $total;
            $total_paid_sum += $paid;

            $label = $status_labels[$status] ?? $status;
            $color = $status_colors[$status] ?? '#94a3b8';

            $rows_html .= '<tr>
                <td>' . esc_html($num) . '</td>
                <td>' . esc_html($inv['created_at'] ? wp_date('d/m/Y', strtotime($inv['created_at'])) : '-') . '</td>
                <td>' . esc_html($inv['due_date'] ? wp_date('d/m/Y', strtotime($inv['due_date'])) : '-') . '</td>
                <td><span style="color:' . $color . ';font-weight:700">' . esc_html($label) . '</span></td>
                <td style="text-align:right">' . number_format($total, 2, ',', ' ') . ' ' . $symbol . '</td>
                <td style="text-align:right">' . number_format($paid, 2, ',', ' ') . ' ' . $symbol . '</td>
                <td style="text-align:right;font-weight:700;color:' . ($due > 0 ? '#ef4444' : '#22c55e') . '">' . number_format($due, 2, ',', ' ') . ' ' . $symbol . '</td>
            </tr>';
        }

        $outstanding = $total_inv - $total_paid_sum;
        $company_name = $client['company_name'] ?? '';
        $generated    = wp_date('d/m/Y');

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">
        <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
            h1 { font-size: 18px; margin-bottom: 4px; color: #1e293b; }
            .subtitle { color: #64748b; font-size: 11px; margin-bottom: 24px; }
            table { width: 100%; border-collapse: collapse; margin-top: 16px; }
            th { background: #f1f5f9; padding: 8px 10px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; }
            td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; }
            .totals { margin-top: 20px; text-align: right; font-size: 11px; }
            .totals td { padding: 4px 10px; border:none; }
            .totals .label { color: #64748b; }
            .totals .value { font-weight: 700; }
            .total-due { font-size: 13px; color: ' . ($outstanding > 0 ? '#ef4444' : '#22c55e') . '; font-weight:900; }
        </style></head><body>
        <h1>Relevé de compte</h1>
        <p class="subtitle">Client : <strong>' . esc_html($company_name) . '</strong> &nbsp;|&nbsp; Généré le ' . $generated . '</p>
        <table>
            <thead><tr>
                <th>N° Facture</th><th>Date</th><th>Échéance</th><th>Statut</th>
                <th style="text-align:right">Montant</th><th style="text-align:right">Payé</th><th style="text-align:right">Solde</th>
            </tr></thead>
            <tbody>' . $rows_html . '</tbody>
        </table>
        <table class="totals" style="width:300px;margin-left:auto;margin-top:20px">
            <tr><td class="label">Total facturé</td><td class="value" style="text-align:right">' . number_format($total_inv, 2, ',', ' ') . ' ' . $symbol . '</td></tr>
            <tr><td class="label">Total encaissé</td><td class="value" style="text-align:right">' . number_format($total_paid_sum, 2, ',', ' ') . ' ' . $symbol . '</td></tr>
            <tr><td class="label total-due">Solde dû</td><td class="value total-due" style="text-align:right">' . number_format($outstanding, 2, ',', ' ') . ' ' . $symbol . '</td></tr>
        </table>
        </body></html>';

        try {
            $mpdf = new \Mpdf\Mpdf([
                'margin_left'   => 12,
                'margin_right'  => 12,
                'margin_top'    => 15,
                'margin_bottom' => 15,
                'default_font_size' => 10,
                'mode'   => 'utf-8',
                'format' => 'A4',
            ]);
            $mpdf->WriteHTML($html);
            $filename = 'releve-' . sanitize_file_name($company_name) . '-' . wp_date('Y-m-d') . '.pdf';
            $mpdf->Output($filename, 'D');
            exit;
        } catch (\Exception $e) {
            return new \WP_Error('pdf_error', $e->getMessage(), ['status' => 500]);
        }
    }
}
