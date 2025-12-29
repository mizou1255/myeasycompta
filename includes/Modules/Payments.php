<?php
namespace ECWP\Admin;

use ECWP\API\Routes;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class ECWP_Payments
{
    protected $routes;
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_submenu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        $this->routes = new Routes();
        $this->register_api_routes();
    }

    public function add_submenu_page()
    {
        add_submenu_page(
            'my-easy-compta',
            __('Payments', 'my-easy-compta'),
            __('Payments', 'my-easy-compta'),
            'manage_options',
            'my-easy-compta-payments',
            array($this, 'render_page'),
            5
        );
    }
    public function enqueue_scripts($hook_suffix)
    {
        if ('myeasycompta_page_my-easy-compta-payments' === $hook_suffix) {
            wp_enqueue_script('my-easy-compta-payments', ECWP_URL . '/assets/dist/payments.min.js', array(), ECWP_VERSION, true);
        }
    }

    public function render_page()
    {
        echo '<div id="my-easy-compta-payments-app" class="ecwp-content"></div>';
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
        $per_page = isset($request['per_page']) ? intval($request['per_page']) : 10;
        $page = isset($request['page']) ? intval($request['page']) : 1;
        $offset = ($page - 1) * $per_page;

        $where_clauses = [];
        $query_params = [];

        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        // Récupérer toutes les factures sans filtre direct sur les champs chiffrés
        $payments_table = ECWP_TABLE_PAYMENTS;
        $clients_table = ECWP_TABLE_CLIENTS;
        $invoices_table = ECWP_TABLE_INVOICES;
        $methods_table = ECWP_TABLE_PAYMENTS_METHODS;
        $currency_table = ECWP_TABLE_CURRENCY;

        // Récupération sans pagination pour filtrage manuel
        $query = "SELECT p.*, c.company_name, m.method_name, i.invoice_number, o.symbol
                  FROM {$payments_table} p
                  LEFT JOIN {$clients_table} c ON p.client_id = c.id
                  LEFT JOIN {$invoices_table} i ON p.invoice_id = i.id
                  LEFT JOIN {$methods_table} m ON p.payment_method_id = m.id
                  LEFT JOIN {$currency_table} o ON c.currency_id = o.id
                  ORDER BY p.id DESC";

        $payments = $wpdb->get_results($query, OBJECT);

        $settings = new \ECWP\Admin\Settings\ECWP_Settings();
        $format_date_response = $settings->get_format_date();
        $format_date = isset($format_date_response->data) ? $format_date_response->data : 'Y-m-d';

        $filtered_data = [];
        foreach ($payments as $payment) {
            $decrypted_invoice_number = $encrypt->decrypt($payment->invoice_number);
            $match = true;

            if (!empty($request['invoice_number']) && stripos($decrypted_invoice_number, $request['invoice_number']) === false) {
                $match = false;
            }
            if (!empty($request['client']) && stripos($payment->company_name, $request['client']) === false) {
                $match = false;
            }
            if (!empty($request['payment_method']) && $payment->method_name !== $request['payment_method']) {
                $match = false;
            }
            if (!empty($request['payment_date']) && date('Y-m-d', strtotime($payment->payment_date)) !== $request['payment_date']) {
                $match = false;
            }

            if ($match) {
                $filtered_data[] = [
                    'id' => $payment->id,
                    'company_name' => $payment->company_name,
                    'client_currency' => $payment->symbol,
                    'invoice_number' => $decrypted_invoice_number,
                    'amount' => number_format(floatval($payment->amount), 2, '.', ''),
                    'payment_method' => $payment->method_name,
                    'payment_date' => date_i18n($format_date, strtotime($payment->payment_date)),
                    'notes' => $payment->notes,
                ];
            }
        }

        $total_count = count($filtered_data);
        $total_pages = ceil($total_count / $per_page);

        // Pagination des données filtrées
        $paged_data = array_slice($filtered_data, $offset, $per_page);

        $response = array(
            'payments' => $paged_data,
            'total_count' => $total_count,
            'total_pages' => $total_pages,
            'page' => $page,
            'per_page' => $per_page,
            'filters' => [
                'invoice_number' => $request['invoice_number'] ?? '',
                'client' => $request['client'] ?? '',
                'payment_method' => $request['payment_method'] ?? '',
                'payment_date' => $request['payment_date'] ?? '',
            ],
        );

        return rest_ensure_response($response);
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
        $payment_id = isset($params['id']) ? intval($params['id']) : 0;

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

        $amount = $request->get_param('amount');
        $payment_date = $request->get_param('payment_date');
        $payment_method_id = $request->get_param('payment_method_id');
        $notes = $request->get_param('notes');
        $payment_data = array(
            'amount' => $amount,
            'payment_date' => $payment_date,
            'payment_method_id' => $payment_method_id,
            'notes' => $notes,
        );

        $result = $wpdb->update(
            ECWP_TABLE_PAYMENTS,
            $payment_data,
            array('id' => $payment_id),
            array(
                '%f',
                '%s',
                '%d',
                '%s',
            ),
            array(
                '%d',
            )
        );

        if ($result === false) {
            return new WP_REST_Response(array('success' => false, 'message' => __('Failed to edit payment', 'my-easy-compta')), 500);
        }
        return new WP_REST_Response(array('success' => true, 'message' => __('Payment edited successfully', 'my-easy-compta')), 200);
    }

    public function delete_payment($request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        $payment_id = $request['id'];

        global $wpdb;

        $result = $wpdb->delete(
            ECWP_TABLE_PAYMENTS,
            array('id' => $payment_id),
            array('%d')
        );

        if ($result) {
            return new WP_REST_Response(array('success' => true, 'message' => __('Payment deleted successfully', 'my-easy-compta')), 200);
        } else {
            return new WP_REST_Response(array('success' => false, 'message' => __('Failed to delete payment', 'my-easy-compta')), 500);
        }
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
