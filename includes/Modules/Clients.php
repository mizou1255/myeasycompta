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
        add_action('admin_menu', array($this, 'add_submenu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        $this->routes = new Routes();
        $this->register_api_routes();
    }

    /**
     * @return [type]
     */
    public function add_submenu_page()
    {
        add_submenu_page(
            'my-easy-compta',
            __('Clients', 'my-easy-compta'),
            __('Clients', 'my-easy-compta'),
            'manage_options',
            'my-easy-compta-clients',
            array($this, 'render_page'),
            2
        );
    }

    /**
     * @return [type]
     */
    public function render_page()
    {
        echo '<div id="my-easy-compta-clients-app" class="ecwp-content"></div>';
    }

    /**
     * @param mixed $hook_suffix
     *
     * @return [type]
     */
    public function enqueue_scripts($hook_suffix)
    {
        if ('myeasycompta_page_my-easy-compta-clients' === $hook_suffix) {
            wp_enqueue_script('my-easy-compta-clients', ECWP_URL . '/assets/dist/clients.min.js', array(), ECWP_VERSION, true);
        }
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
        $total_count = $wpdb->get_var("SELECT COUNT(*) FROM %i", ECWP_TABLE_CLIENTS);
        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM %i ORDER BY company_name ASC  LIMIT %d OFFSET %d", ECWP_TABLE_CLIENTS,
                $per_page, $offset),
            OBJECT
        );

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
        $results = $wpdb->get_results($wpdb->prepare("SELECT
        c.id,
        c.company_name,
        c.email,
        c.currency_id,
        cur.symbol as currency_symbol
    FROM
    %i c
    LEFT JOIN
    %i cur ON c.currency_id = cur.id
    ORDER BY
        c.company_name ASC",
            ECWP_TABLE_CLIENTS, ECWP_TABLE_CURRENCY),
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
        $client_details = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM %i WHERE id = %d", ECWP_TABLE_CLIENTS, $client_id),
            ARRAY_A
        );
        if (!$client_details) {
            return new WP_Error('client_not_found', __('Client not found.', 'my-easy-compta'), array('status' => 404));
        }
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
            error_log('Invalid Nonce: ' . $nonce);
            return new WP_Error('rest_nonce_invalid', __('Nonce invalide', 'my-easy-compta'), array('status' => 403));
        }

        if (!$has_permission) {
            error_log('Permission Denied for User: ' . get_current_user_id());
            return new WP_Error('rest_forbidden', __('Error API access', 'my-easy-compta'), array('status' => 403));
        }

        $params = $request->get_params();
        $required_fields = ['company_name', 'email'];

        foreach ($required_fields as $field) {
            if (empty($params[$field])) {
                return new WP_Error(
                    'rest_missing_param',
                    // Translators: %s is the placeholder for the field name.
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
        $existing_client = $wpdb->get_row(
            $wpdb->prepare("SELECT id FROM %i WHERE company_name = %s AND email = %s", ECWP_TABLE_CLIENTS,
                $company_name, $email));

        if ($existing_client) {
            return new WP_Error('rest_client_exists', __('Client already exists', 'my-easy-compta'), array('status' => 409));
        }

        if (!empty($params['currency_id'])) {
            $currency_id = sanitize_text_field($params['currency_id']);
            $currency_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM %i WHERE id = %d", ECWP_TABLE_CURRENCY, $currency_id));

            if (!$currency_exists) {
                return new WP_Error('rest_invalid_param', __('Currency ID is invalid', 'my-easy-compta'), array('status' => 422));
            }
        }

        $data = array(
            'company_name' => $company_name,
            'siren_number' => sanitize_text_field($params['siren_number'] ?? ''),
            'manager_name' => sanitize_text_field($params['manager_name'] ?? ''),
            'address' => sanitize_text_field($params['address'] ?? ''),
            'city' => sanitize_text_field($params['city'] ?? ''),
            'postal_code' => sanitize_text_field($params['postal_code'] ?? ''),
            'country' => sanitize_text_field($params['country'] ?? ''),
            'phone' => sanitize_text_field($params['phone'] ?? ''),
            'mobile_phone' => sanitize_text_field($params['mobile_phone'] ?? ''),
            'email' => $email,
            'website' => esc_url($params['website'] ?? ''),
            'currency_id' => sanitize_text_field($params['currency_id'] ?? ''),
            'note' => sanitize_textarea_field($params['note'] ?? ''),
        );

        if (isset($params['user_create']) && $params['user_create']) {
            $data['user_create'] = $params['user_create'];
        }

        if (isset($params['siret']) && $params['siret']) {
            $data['siret_number'] = $params['siret'];
        }

        $result = $wpdb->insert(ECWP_TABLE_CLIENTS, $data);

        if (false === $result) {
            error_log('SQL Error: ' . $wpdb->last_error);
            return new WP_REST_Response(array('success' => false, 'data' => $data, 'message' => __('Failed to add client', 'my-easy-compta')), 500);
        } else {
            $inserted_id = $wpdb->insert_id;
            $data['id'] = $inserted_id;
            do_action('ecwp_add_user', $data);
            return new WP_REST_Response(array('success' => true, 'message' => __('Client added successfully', 'my-easy-compta')), 200);
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
        $data = $request->get_params();
        unset($data['id']);
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
        $client_id = isset($request['id']) ? $request['id'] : null;
        $client_id = absint($client_id);
        global $wpdb;
        $associated_data_exists = $this->check_associated_data($client_id);
        if ($associated_data_exists) {

            $quotes = $wpdb->get_results(
                $wpdb->prepare("SELECT id FROM %i WHERE client_id = %d", ECWP_TABLE_QUOTES, $client_id),
                ARRAY_A
            );
            foreach ($quotes as $quote) {
                $wpdb->delete(ECWP_TABLE_QUOTE_ELEMENTS, array('quote_id' => $quote['id']));
                $wpdb->delete(ECWP_TABLE_QUOTES, array('id' => $quote['id']));
            }

            $invoices = $wpdb->get_results(
                $wpdb->prepare("SELECT id FROM %i WHERE client_id = %d", ECWP_TABLE_INVOICES, $client_id),
                ARRAY_A
            );
            foreach ($invoices as $invoice) {
                $wpdb->delete(ECWP_TABLE_INVOICE_ELEMENTS, array('invoice_id' => $invoice['id']));
                $wpdb->delete(ECWP_TABLE_INVOICES, array('id' => $invoice['id']));
            }

            $payments = $wpdb->get_results(
                $wpdb->prepare("SELECT id FROM %i WHERE client_id = %d", ECWP_TABLE_PAYMENTS, $client_id),
                ARRAY_A
            );
            foreach ($payments as $payment) {
                $wpdb->delete(ECWP_TABLE_PAYMENTS, array('id' => $payment['id']));
            }
        }

        $result = $wpdb->delete(ECWP_TABLE_CLIENTS, array('id' => $client_id));

        if ($result) {
            return new WP_REST_Response(array('success' => true, 'message' => __('Client deleted successfully', 'my-easy-compta')), 200);
        } else {
            return new WP_REST_Response(array('success' => false, 'message' => __('Failed to delete client', 'my-easy-compta')), 500);
        }
    }

    /**
     * @param mixed $client_id
     *
     * @return [type]
     */
    private function check_associated_data($client_id)
    {
        global $wpdb;
        $quote_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM %i WHERE client_id = %d", ECWP_TABLE_QUOTES, $client_id));
        $invoice_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM %i WHERE client_id = %d", ECWP_TABLE_INVOICES, $client_id));
        $payment_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM %i WHERE client_id = %d", ECWP_TABLE_PAYMENTS, $client_id));
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
        $currency_options = $wpdb->get_results($wpdb->prepare("SELECT id, name, code, symbol FROM %i", ECWP_TABLE_CURRENCY));
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
}
