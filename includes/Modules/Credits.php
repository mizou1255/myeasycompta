<?php
namespace ECWP\Admin;

use ECWP\Admin\PDF\PDFGenerator;
use ECWP\API\Routes;

class ECWP_Credits
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
        $this->routes->add_route('/credits', 'GET', $this, 'get_credits', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/invoices/credit', 'POST', $this, 'create_credit_invoice', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/invoices/(?P<id>\d+)/credit', 'POST', $this, 'create_credit_invoice', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/credits/(?P<id>\d+)', 'DELETE', $this, 'delete_credit', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/credits/pdf/(?P<id>\d+)', 'GET', $this, 'generate_credit_pdf', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/credits/find-page/(?P<id>\d+)', 'GET', $this, 'find_credit_page', function () {
            return current_user_can('manage_options');
        });

        $this->routes->register_routes();
    }

    public function get_credits($request)
    {
        global $wpdb;

        $per_page = isset($request['per_page']) ? intval($request['per_page']) : 10;
        $page = isset($request['page']) ? intval($request['page']) : 1;
        $offset = ($page - 1) * $per_page;

        $invoices_table = ECWP_TABLE_INVOICES;
        $credits_table = ECWP_TABLE_CREDITS;
        $clients_table = ECWP_TABLE_CLIENTS;
        $currencies_table = ECWP_TABLE_CURRENCY;
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT invoices.id,
                        clients.company_name,
                        currencies.symbol AS currency_symbole,
                        invoices.invoice_number,
                        invoices.total_amount,
                        invoices.due_date,
                        credits.id AS credit_id,
                        credits.credit_number,
                        credits.created_at
                FROM {$invoices_table} AS invoices
                LEFT JOIN {$credits_table} AS credits ON invoices.id = credits.invoice_id
                LEFT JOIN {$clients_table} AS clients ON invoices.client_id = clients.id
                LEFT JOIN {$currencies_table} AS currencies ON clients.currency_id = currencies.id
                WHERE invoices.credit = %d
                ORDER BY invoices.id DESC
                LIMIT %d OFFSET %d",
                1,
                $per_page,
                $offset
            ),
            OBJECT
        );
        $settings = new \ECWP\Admin\Settings\ECWP_Settings();
        $format_date_response = $settings->get_format_date();
        $format_date = isset($format_date_response->data) ? $format_date_response->data : 'Y-m-d';

        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        $data = array();
        foreach ($results as $r) {
            $data[] = array(
                'id' => $r->id,
                'client_name' => $r->company_name,
                'client_currency' => $r->currency_symbole,
                'credit_id' => $r->credit_id,
                'credit_number' => $r->credit_number,
                'invoice_number' => $encrypt->decrypt($r->invoice_number),
                'total_amount' => number_format(floatval($encrypt->decrypt($r->total_amount)), 2, '.', ''),
                'due_date' => date_i18n($format_date, strtotime($r->due_date)),
                'created_at' => date_i18n($format_date, strtotime($r->created_at)),
            );
        }
        $invoices_table = ECWP_TABLE_INVOICES;
        $total_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$invoices_table} WHERE credit = %d", 1));
        $total_pages = ceil($total_count / $per_page);

        $response = array(
            'credits' => $data,
            'total_count' => $total_count,
            'total_pages' => $total_pages,
            'page' => $page,
            'per_page' => $per_page,
        );

        return rest_ensure_response($response);
    }

    /**
     * Trouve la page où se trouve un avoir spécifique
     * Note: Les avoirs utilisent invoices.id comme identifiant principal
     */
    public function find_credit_page(\WP_REST_Request $request)
    {
        global $wpdb;
        $credit_id = absint($request->get_param('id'));
        $per_page = $request->get_param('per_page') ? intval($request->get_param('per_page')) : 10;

        if ($credit_id <= 0) {
            return new \WP_Error('invalid_credit_id', __('Invalid credit ID.', 'my-easy-compta'), array('status' => 400));
        }

        $invoices_table = ECWP_TABLE_INVOICES;

        // Compter combien d'avoirs (invoices avec credit=1) ont un ID supérieur (triés par ID DESC)
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$invoices_table} WHERE credit = 1 AND id > %d",
            $credit_id
        ));

        // La page est calculée en fonction de la position dans la liste triée
        $page = floor($count / $per_page) + 1;

        return rest_ensure_response(array(
            'page' => $page,
            'per_page' => $per_page
        ));
    }

    public function create_credit_invoice($request)
    {
        global $wpdb;
        $invoice_id = absint($request['id']);
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }
        $invoices_table = ECWP_TABLE_INVOICES;
        $invoice = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$invoices_table} WHERE id = %d", $invoice_id),
            ARRAY_A
        );

        if (!$invoice) {
            return new \WP_Error('invoice_not_found', __('Invoice not found', 'my-easy-compta'), array('status' => 404));
        }

        $credits_table = ECWP_TABLE_CREDITS;
        $settings_table = ECWP_TABLE_SETTINGS;

        $wpdb->query('START TRANSACTION');
        $last_credit_id = (int) $wpdb->get_var("SELECT MAX(id) FROM {$credits_table} FOR UPDATE");
        $credit_prefix = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$settings_table} WHERE meta_key = %s", 'credit_prefix'));
        $credit_prefix = $credit_prefix ? sanitize_text_field($credit_prefix) : 'AVR';
        $credit_number_format = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$settings_table} WHERE meta_key = %s", 'credit_number_format')) ?: 'prefix';
        $credit_number = $this->generate_document_number($credit_prefix, $credit_number_format, $credits_table, $last_credit_id + 1);
        $inserted = $wpdb->insert(
            ECWP_TABLE_CREDITS,
            array(
                'credit_number' => $credit_number,
                'invoice_id' => $invoice_id,
                'created_at' => current_time('Y-m-d'),
            ),
            array('%s', '%d', '%s')
        );

        if ($inserted === false) {
            $wpdb->query('ROLLBACK');
            return new \WP_REST_Response(array('success' => false, 'message' => __('Failed to create credit invoice', 'my-easy-compta')), 500);
        }

        $credit_id = $wpdb->insert_id;

        $wpdb->update(
            ECWP_TABLE_INVOICES,
            array('credit' => 1),
            array('id' => $invoice_id),
            array('%d'),
            array('%d')
        );

        $wpdb->query('COMMIT');

        return new \WP_REST_Response(array(
            'success' => true,
            'message' => __('Credit invoice created successfully', 'my-easy-compta'),
            'id' => $invoice_id,
            'credit_number' => $credit_number,
        ), 200);
    }

    public function delete_credit($request)
    {
        global $wpdb;

        $invoice_id = absint($request['id']);
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));

        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        $invoices_table = ECWP_TABLE_INVOICES;
        $invoice = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$invoices_table} WHERE id = %d", $invoice_id),
            ARRAY_A
        );

        if (!$invoice) {
            return new \WP_Error('invoice_not_found', __('Invoice not found', 'my-easy-compta'), array('status' => 404));
        }

        $wpdb->query('START TRANSACTION');

        $wpdb->delete(ECWP_TABLE_CREDITS, array('invoice_id' => $invoice_id), array('%d'));

        $result = $wpdb->update(
            ECWP_TABLE_INVOICES,
            array('credit' => 0),
            array('id' => $invoice_id),
            array('%d'),
            array('%d')
        );

        if ($result === false) {
            $wpdb->query('ROLLBACK');
            return new \WP_Error('update_failed', __('Failed to update invoice', 'my-easy-compta'), array('status' => 500));
        }

        $wpdb->query('COMMIT');

        return new \WP_REST_Response(array('success' => true, 'message' => __('Credit invoice removed successfully', 'my-easy-compta'), 'id' => $invoice_id), 200);
    }

    public function generate_credit_pdf(\WP_REST_Request $request)
    {
        global $wpdb;
        $credit_id = absint($request->get_param('id'));
        $currency_id = absint($request->get_param('currency_id')) ?: null;

        $pdfGenerator = new PDFGenerator($wpdb);
        $pdfGenerator->generateCreditPDF($credit_id, $currency_id);
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

}
