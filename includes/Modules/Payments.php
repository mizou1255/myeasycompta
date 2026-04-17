<?php
namespace ECWP\Admin;

use ECWP\API\Routes;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use ECWP\Admin\Encrypt\ECWP_Encrypt;

class ECWP_Payments
{
    protected $routes;
    public function __construct()
    {
        // Plus de sous-menu WordPress - navigation SPA uniquement
        $this->routes = new Routes();
        $this->register_api_routes();
    }

    private function register_api_routes()
    {
        $this->routes->add_route('/payments', 'GET', $this, 'get_payments', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/payments/find-page/(?P<id>\d+)', 'GET', $this, 'find_payment_page', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/payments/methods', 'GET', $this, 'get_payment_methods', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/payments/details/(?P<id>\d+)', 'GET', $this, 'get_payment_details', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/payments/(?P<id>\d+)', 'PUT', $this, 'update_payment', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/payments/(?P<id>\d+)', 'DELETE', $this, 'delete_payment', function () {
            return current_user_can('manage_options');
        });

        $this->routes->register_routes();
    }

    public function get_payments($request)
    {
        global $wpdb;

        $per_page = max(1, absint($request['per_page'] ?? 10));
        $page     = max(1, absint($request['page']     ?? 1));
        $offset   = ($page - 1) * $per_page;

        $encrypt  = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        $settings = new \ECWP\Admin\Settings\ECWP_Settings();
        $format_date_response = $settings->get_format_date();
        $format_date = $format_date_response->data ?? 'Y-m-d';

        $payments_table = ECWP_TABLE_PAYMENTS;
        $clients_table  = ECWP_TABLE_CLIENTS;
        $invoices_table = ECWP_TABLE_INVOICES;
        $methods_table  = ECWP_TABLE_PAYMENTS_METHODS;
        $currency_table = ECWP_TABLE_CURRENCY;

        $filter_invoice_number = trim($request['invoice_number'] ?? '');
        $filter_client         = trim($request['client']         ?? '');
        $filter_method         = trim($request['payment_method'] ?? '');
        $filter_date           = trim($request['payment_date']   ?? '');

        // Build SQL WHERE for plaintext-filterable columns
        $where_parts  = ['1=1'];
        $query_params = [];

        if ($filter_client !== '') {
            $where_parts[]  = 'c.company_name LIKE %s';
            $query_params[] = '%' . $wpdb->esc_like($filter_client) . '%';
        }
        if ($filter_method !== '') {
            $where_parts[]  = 'm.method_name = %s';
            $query_params[] = $filter_method;
        }
        if ($filter_date !== '') {
            $where_parts[]  = 'DATE(p.payment_date) = %s';
            $query_params[] = $filter_date;
        }

        $filter_date_from = trim($request['date_from'] ?? '');
        $filter_date_to   = trim($request['date_to']   ?? '');
        if ($filter_date_from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_date_from)) {
            $where_parts[]  = 'p.payment_date >= %s';
            $query_params[] = $filter_date_from . ' 00:00:00';
        }
        if ($filter_date_to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_date_to)) {
            $where_parts[]  = 'p.payment_date <= %s';
            $query_params[] = $filter_date_to . ' 23:59:59';
        }

        $where_sql = implode(' AND ', $where_parts);

        $base_from = "FROM {$payments_table} p
                      LEFT JOIN {$clients_table} c  ON p.client_id = c.id
                      LEFT JOIN {$invoices_table} i ON p.invoice_id = i.id
                      LEFT JOIN {$methods_table} m  ON p.payment_method_id = m.id
                      LEFT JOIN {$currency_table} o ON c.currency_id = o.id
                      WHERE {$where_sql}";

        if ($filter_invoice_number !== '') {
            // invoice_number is encrypted — fetch all plaintext-filtered rows, then filter in PHP
            $sql  = "SELECT p.*, c.company_name, m.method_name, i.invoice_number, o.symbol {$base_from} ORDER BY p.id DESC";
            $rows = $query_params
                ? $wpdb->get_results($wpdb->prepare($sql, $query_params), OBJECT)
                : $wpdb->get_results($sql, OBJECT);

            $filtered = [];
            foreach ($rows as $row) {
                $decrypted = $encrypt->decrypt($row->invoice_number);
                if (stripos($decrypted, $filter_invoice_number) !== false) {
                    $filtered[] = $this->format_payment_row($row, $decrypted, $format_date);
                }
            }

            $total_count = count($filtered);
            $total_pages = (int) ceil($total_count / $per_page);
            $paged_data  = array_slice($filtered, $offset, $per_page);
        } else {
            // All filters are plaintext → fully SQL-paginated (efficient)
            $count_sql   = "SELECT COUNT(*) {$base_from}";
            $total_count = (int) ($query_params
                ? $wpdb->get_var($wpdb->prepare($count_sql, $query_params))
                : $wpdb->get_var($count_sql));

            $total_pages = (int) ceil($total_count / $per_page);

            $data_sql    = "SELECT p.*, c.company_name, m.method_name, i.invoice_number, o.symbol {$base_from} ORDER BY p.id DESC LIMIT %d OFFSET %d";
            $data_params = array_merge($query_params, [$per_page, $offset]);
            $rows        = $wpdb->get_results($wpdb->prepare($data_sql, $data_params), OBJECT);

            $paged_data = [];
            foreach ($rows as $row) {
                $paged_data[] = $this->format_payment_row($row, $encrypt->decrypt($row->invoice_number), $format_date);
            }
        }

        return rest_ensure_response([
            'payments'    => $paged_data,
            'total_count' => $total_count,
            'total_pages' => $total_pages,
            'page'        => $page,
            'per_page'    => $per_page,
            'filters'     => [
                'invoice_number' => $filter_invoice_number,
                'client'         => $filter_client,
                'payment_method' => $filter_method,
                'payment_date'   => $filter_date,
            ],
        ]);
    }

    private function format_payment_row(object $row, string $invoice_number, string $format_date): array
    {
        return [
            'id'              => $row->id,
            'company_name'    => $row->company_name,
            'client_currency' => $row->symbol,
            'invoice_number'  => $invoice_number,
            'amount'          => number_format(floatval($row->amount), 2, '.', ''),
            'payment_method'  => $row->method_name,
            'payment_date'    => date_i18n($format_date, strtotime($row->payment_date)),
            'notes'           => $row->notes,
        ];
    }

    /**
     * Trouve la page où se trouve un paiement spécifique
     */
    public function find_payment_page(WP_REST_Request $request)
    {
        global $wpdb;
        $payment_id = absint($request->get_param('id'));
        $per_page = isset($request['per_page']) ? intval($request['per_page']) : 10;
        
        if ($payment_id <= 0) {
            return new WP_Error('invalid_payment_id', __('Invalid payment ID.', 'my-easy-compta'), array('status' => 400));
        }

        $payments_table = ECWP_TABLE_PAYMENTS;
        
        // Compter combien de paiements ont un ID supérieur (triés par ID DESC)
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$payments_table} WHERE id > %d",
            $payment_id
        ));
        
        // La page est calculée en fonction de la position dans la liste triée
        $page = floor($count / $per_page) + 1;
        
        return rest_ensure_response(array(
            'page' => $page,
            'per_page' => $per_page
        ));
    }

    public function get_payment_details(WP_REST_Request $request)
    {
        global $wpdb;
        $params = $request->get_params();
        $payment_id = isset($params['id']) ? absint($params['id']) : 0;

        if ($payment_id <= 0) {
            return new WP_Error('invalid_payment_id', __('Invalid payment ID.', 'my-easy-compta'), array('status' => 400));
        }

        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        
        // Définir les noms de tables
        $payments_table = ECWP_TABLE_PAYMENTS;
        $invoices_table = ECWP_TABLE_INVOICES;
        $clients_table = ECWP_TABLE_CLIENTS;

        $payment_details = $wpdb->get_row(
            $wpdb->prepare("SELECT p.*, i.invoice_number, c.company_name
            FROM {$payments_table} p
            LEFT JOIN {$invoices_table} i ON p.invoice_id = i.id
            LEFT JOIN {$clients_table} c ON i.client_id = c.id
            WHERE p.id = %d",
                $payment_id),
            ARRAY_A
        );

        if (!$payment_details) {
            return new WP_Error('payment_not_found', __('Payment not found.', 'my-easy-compta'), array('status' => 404));
        }

        if (isset($payment_details['invoice_number'])) {
            $payment_details['invoice_number'] = $encrypt->decrypt($payment_details['invoice_number']);
        }

        $payments_methods_table = ECWP_TABLE_PAYMENTS_METHODS;
        $payment_methods = $wpdb->get_results("SELECT * FROM {$payments_methods_table}");

        $payment_details['payment_methods'] = $payment_methods;

        return rest_ensure_response($payment_details);
    }

    /**
     * Recalcule paid_amount et met à jour le statut commercial de la facture.
     *
     * @param int $invoice_id
     */
    private function sync_invoice_paid_amount(int $invoice_id): void
    {
        global $wpdb;

        $encrypt        = new ECWP_Encrypt();
        $invoices_table = ECWP_TABLE_INVOICES;
        $payments_table = ECWP_TABLE_PAYMENTS;

        $invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT total_amount FROM {$invoices_table} WHERE id = %d",
            $invoice_id
        ));

        if (!$invoice) {
            return;
        }

        $total_amount = floatval($encrypt->decrypt($invoice->total_amount));

        $new_paid = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM {$payments_table} WHERE invoice_id = %d",
            $invoice_id
        ));

        $new_status = ($new_paid >= $total_amount - 0.005) ? 'paid' : (($new_paid > 0) ? 'partial' : 'unpaid');

        $wpdb->update(
            $invoices_table,
            [
                'paid_amount'  => $new_paid,
                'status'       => $encrypt->encrypt($new_status),
                'status_stats' => $new_status,
            ],
            ['id' => $invoice_id],
            ['%f', '%s', '%s'],
            ['%d']
        );
    }

    public function update_payment(WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $payment_id = $request->get_param('id');
        if (empty($payment_id) || !is_numeric($payment_id)) {
            return new WP_Error('invalid_payment_id', 'ID payment invalid.', array('status' => 400));
        }

        // Récupérer l'invoice_id avant mise à jour pour recalcul paid_amount
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT invoice_id FROM " . ECWP_TABLE_PAYMENTS . " WHERE id = %d",
            intval($payment_id)
        ));

        $amount            = floatval($request->get_param('amount'));
        $payment_date      = sanitize_text_field($request->get_param('payment_date'));
        $payment_method_id = absint($request->get_param('payment_method_id'));
        $notes             = sanitize_textarea_field($request->get_param('notes'));
        $payment_data = array(
            'amount'            => $amount,
            'payment_date'      => $payment_date,
            'payment_method_id' => $payment_method_id,
            'notes'             => $notes,
        );

        $result = $wpdb->update(
            ECWP_TABLE_PAYMENTS,
            $payment_data,
            array('id' => $payment_id),
            array('%f', '%s', '%d', '%s'),
            array('%d')
        );

        if ($result === false) {
            return new WP_REST_Response(array('success' => false, 'message' => __('Failed to edit payment', 'my-easy-compta')), 500);
        }

        // Recalculer paid_amount sur la facture
        if ($payment && $payment->invoice_id) {
            $this->sync_invoice_paid_amount(intval($payment->invoice_id));
        }

        return new WP_REST_Response(array('success' => true, 'message' => __('Payment edited successfully', 'my-easy-compta')), 200);
    }

    public function delete_payment($request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }

        global $wpdb;
        $payment_id = absint($request['id']);

        // Récupérer l'invoice_id avant suppression
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT invoice_id FROM " . ECWP_TABLE_PAYMENTS . " WHERE id = %d",
            $payment_id
        ));

        $result = $wpdb->delete(ECWP_TABLE_PAYMENTS, ['id' => $payment_id], ['%d']);

        if (!$result) {
            return new WP_REST_Response(['success' => false, 'message' => __('Failed to delete payment', 'my-easy-compta')], 500);
        }

        // Recalculer paid_amount sur la facture
        if ($payment && $payment->invoice_id) {
            $this->sync_invoice_paid_amount(intval($payment->invoice_id));
        }

        return new WP_REST_Response(['success' => true, 'message' => __('Payment deleted successfully', 'my-easy-compta')], 200);
    }

    public function get_payment_methods($request)
    {

        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $payments_methods_table = ECWP_TABLE_PAYMENTS_METHODS;
        $payment_methods = $wpdb->get_results("SELECT * FROM {$payments_methods_table}");

        return new WP_REST_Response($payment_methods, 200);
    }
}
