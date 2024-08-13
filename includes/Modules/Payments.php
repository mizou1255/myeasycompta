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
            4
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
        $total_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM %i", ECWP_TABLE_PAYMENTS));
        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT p.*, c.company_name, m.method_name, i.invoice_number, o.symbol
        FROM %i p
        LEFT JOIN %i c ON p.client_id = c.id
        LEFT JOIN %i i ON p.invoice_id = i.id
        LEFT JOIN %i m ON p.payment_method_id = m.id
        LEFT JOIN %i o ON c.currency_id = o.id
        ORDER BY p.id DESC
        LIMIT %d OFFSET %d",
                ECWP_TABLE_PAYMENTS, ECWP_TABLE_CLIENTS, ECWP_TABLE_INVOICES, ECWP_TABLE_PAYMENTS_METHODS, ECWP_TABLE_CURRENCY,
                $per_page, $offset),
            ARRAY_A
        );
        $settings = new \ECWP\Admin\Settings\ECWP_Settings();
        $format_date_response = $settings->get_format_date();
        $format_date = isset($format_date_response->data) ? $format_date_response->data : 'Y-m-d';

        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        foreach ($results as &$result) {
            if (isset($result['invoice_number'])) {
                $result['invoice_number'] = $encrypt->decrypt($result['invoice_number']);
            }

            if (isset($result['payment_date'])) {
                $result['payment_date'] = date_i18n($format_date, strtotime($result['payment_date']));
            }
        }

        $total_pages = ceil($total_count / $per_page);
        $response = array(
            'payments' => $results,
            'total_count' => $total_count,
            'total_pages' => $total_pages,
            'page' => $page,
            'per_page' => $per_page,
        );

        return rest_ensure_response($response);
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

        $payment_details = $wpdb->get_row(
            $wpdb->prepare("SELECT p.*, i.invoice_number, c.company_name
            FROM %i p
            LEFT JOIN %i i ON p.invoice_id = i.id
            LEFT JOIN %i c ON i.client_id = c.id
            WHERE p.id = %d",
                ECWP_TABLE_PAYMENTS, ECWP_TABLE_INVOICES, ECWP_TABLE_CLIENTS,
                $payment_id),
            ARRAY_A
        );

        if (!$payment_details) {
            return new WP_Error('payment_not_found', __('Payment not found.', 'my-easy-compta'), array('status' => 404));
        }

        if (isset($payment_details['invoice_number'])) {
            $payment_details['invoice_number'] = $encrypt->decrypt($payment_details['invoice_number']);
        }

        $payment_methods = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i", ECWP_TABLE_PAYMENTS_METHODS));

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

}
