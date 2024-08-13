<?php

namespace ECWP\Admin\Settings;

use ECWP\API\Routes;

class ECWP_Settings
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
            __('Settings', 'my-easy-compta'),
            __('Settings', 'my-easy-compta'),
            'manage_options',
            'my-easy-compta-settings',
            array($this, 'render_page'),
            16
        );
    }

    /**
     * @param mixed $hook_suffix
     *
     * @return [type]
     */
    public function enqueue_scripts($hook_suffix)
    {
        if ('myeasycompta_page_my-easy-compta-settings' === $hook_suffix) {
            wp_enqueue_script('my-easy-compta-settings', ECWP_URL . '/assets/dist/settings.min.js', array(), ECWP_VERSION, true);
            wp_enqueue_script('my-easy-compta-custom', ECWP_URL . '/assets/js/custom.js', array(), ECWP_VERSION, true);
        }
    }

    /**
     * @return [type]
     */
    public function render_page()
    {
        echo '<div id="my-easy-compta-settings-app" class="ecwp-content"></div>';
    }

    /**
     * @return [type]
     */
    private function register_api_routes()
    {
        $this->routes->add_route('/settings/get', 'GET', $this, 'get_settings', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/settings/save', 'POST', $this, 'save_settings', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/settings/currencies', 'GET', $this, 'get_currencies', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/settings/articles', 'GET', $this, 'get_articles', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/categories-articles', 'GET', $this, 'get_categories', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/settings/vats', 'GET', $this, 'get_vats', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/settings/payments-methods', 'GET', $this, 'get_payments_methods', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/settings/expenses-cat', 'GET', $this, 'get_expenses_categories', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/settings/currency/(?P<id>\d+)', 'GET', $this, 'get_currency', function () {
            return true;
        });
        $this->routes->add_route('/settings/vat/(?P<id>\d+)', 'GET', $this, 'get_vat', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/settings/upload-logo', 'POST', $this, 'upload_logo', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/settings/currencies', 'POST', $this, 'add_currency', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/settings/currencies/(?P<id>\d+)', 'PUT', $this, 'edit_currency', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/settings/currencies/(?P<id>\d+)', 'DELETE', $this, 'delete_currency', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/settings/articles/(?P<id>\d+)', 'DELETE', $this, 'delete_article', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/settings/category/(?P<id>\d+)', 'DELETE', $this, 'delete_category', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/settings/vats', 'POST', $this, 'add_vat', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/settings/vats/(?P<id>\d+)', 'PUT', $this, 'edit_vat', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/settings/vats/(?P<id>\d+)', 'DELETE', $this, 'delete_vat', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/settings/expenses-categories', 'POST', $this, 'add_expenses_categories', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/settings/expenses-categories/(?P<id>\d+)', 'PUT', $this, 'edit_expenses_categories', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/settings/expenses-categories/(?P<id>\d+)', 'DELETE', $this, 'delete_expenses_categories', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/settings/payments-methods', 'POST', $this, 'add_payment_method', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/settings/payments-methods/(?P<id>\d+)', 'PUT', $this, 'edit_payment_method', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/settings/payments-methods/(?P<id>\d+)', 'DELETE', $this, 'delete_payment_method', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/license/validate-license', 'POST', $this, 'validate_license', function () {
            return true;
        });
        $this->routes->add_route('/license/store-license', 'POST', $this, 'store_license', function () {
            return true;
        });
        $this->routes->add_route('/license/check-license', 'GET', $this, 'check_license', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('/license/delete-license', 'DELETE', $this, 'delete_license', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/license/check-update', 'POST', $this, 'check_update_plugin', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/license/download-update', 'POST', $this, 'download_update_plugin', function () {
            return current_user_can('manage_options');
        });

        $this->routes->register_routes();
    }

    /**
     * @return [type]
     */
    public function get_settings()
    {
        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare("SELECT meta_key, meta_value FROM %i", ECWP_TABLE_SETTINGS), OBJECT_K);

        $last_invoice_id = $wpdb->get_var($wpdb->prepare("SELECT MAX(number) AS last_id FROM %i", ECWP_TABLE_INVOICES));
        $last_quote_id = $wpdb->get_var($wpdb->prepare("SELECT MAX(number) AS last_id FROM %i", ECWP_TABLE_QUOTES));

        if (empty($last_invoice_id)) {
            $last_invoice_id = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM %i WHERE meta_key = 'invoice_first'", ECWP_TABLE_SETTINGS));
        } else {
            $last_invoice_id += 1;
        }

        if (empty($last_quote_id)) {
            $last_quote_id = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM %i WHERE meta_key = 'quote_first'", ECWP_TABLE_SETTINGS));
        } else {
            $last_quote_id += 1;
        }

        $settings = [];
        foreach ($results as $key => $value) {
            $settings[$key] = $value->meta_value;
        }
        $settings['last_quote_id'] = $last_quote_id;
        $settings['last_invoice_id'] = $last_invoice_id;

        return rest_ensure_response($settings);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function get_format_date()
    {
        global $wpdb;
        $results = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM %i WHERE meta_key = 'date_format'", ECWP_TABLE_SETTINGS));

        return rest_ensure_response($this->convert_date_format($results));
    }
    public function convert_date_format($user_format)
    {
        $format_map = array(
            'DD-MM-YYYY' => 'd-m-Y',
            'MM-DD-YYYY' => 'm-d-Y',
            'YYYY-MM-DD' => 'Y-m-d',
            'YYYY/MM/DD' => 'Y/m/d',
            'DD/MM/YYYY' => 'd/m/Y',
            'MM/DD/YYYY' => 'm/d/Y',
            'YYYY.MM.DD' => 'Y.m.d',
            'DD.MM.YYYY' => 'd.m.Y',
            'MM.DD.YYYY' => 'm.d.Y',
        );

        return isset($format_map[$user_format]) ? $format_map[$user_format] : 'Y-m-d';
    }
    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function save_settings(\WP_REST_Request $request)
    {
        global $wpdb;
        $settings = $request->get_params();

        foreach ($settings as $meta_key => $meta_value) {
            if ($meta_key === 'logo_url') {
                $meta_value = esc_url_raw($meta_value);
            } else {
                $meta_value = wp_kses_post($meta_value);
            }
            $meta_key_sanitized = sanitize_key($meta_key);
            $existing_setting = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM %i WHERE meta_key = %s", ECWP_TABLE_SETTINGS,
                $meta_key_sanitized)
            );

            if ($existing_setting > 0) {
                $wpdb->update(
                    ECWP_TABLE_SETTINGS,
                    array('meta_value' => $meta_value),
                    array('meta_key' => $meta_key_sanitized),
                    array('%s'),
                    array('%s')
                );
            } else {
                $wpdb->insert(
                    ECWP_TABLE_SETTINGS,
                    array(
                        'meta_key' => $meta_key_sanitized,
                        'meta_value' => $meta_value,
                    ),
                    array(
                        '%s',
                        '%s',
                    )
                );
            }
        }

        return rest_ensure_response(__('Settings saved successfully', 'my-easy-compta'));
    }

    /**
     * @return [type]
     */
    public function get_articles()
    {
        global $wpdb;
        $articles = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i", ECWP_TABLE_ARTICLES), ARRAY_A);
        $categories = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i", ECWP_TABLE_ARTICLES_CATEGORIES), ARRAY_A);
        $response_data = [
            'articles' => $articles,
            'categories' => $categories,
        ];
        return rest_ensure_response($response_data);
    }
    /**
     * @return [type]
     */
    public function get_categories()
    {
        global $wpdb;
        $categories = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i", ECWP_TABLE_ARTICLES_CATEGORIES), ARRAY_A);
        return rest_ensure_response($categories);
    }

    /**
     * @return [type]
     */
    public function get_currencies()
    {
        global $wpdb;
        $currencies = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i", ECWP_TABLE_CURRENCY), ARRAY_A);
        return rest_ensure_response($currencies);
    }

    /**
     * @return [type]
     */
    public function get_vats()
    {
        global $wpdb;
        $vats = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i", ECWP_TABLE_VATS), ARRAY_A);
        return rest_ensure_response($vats);
    }

    /**
     * @return [type]
     */
    public function get_payments_methods()
    {
        global $wpdb;
        $payments = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i", ECWP_TABLE_PAYMENTS_METHODS), ARRAY_A);
        return rest_ensure_response($payments);
    }
    /**
     * @return [type]
     */
    public function get_expenses_categories()
    {
        global $wpdb;
        $exps = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i", ECWP_TABLE_EXPENSES_CATEGORIES), ARRAY_A);
        return rest_ensure_response($exps);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function get_currency(\WP_REST_Request $request)
    {
        global $wpdb;
        $currency_id = $request->get_param('id');
        $currency_data = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM %i WHERE id = %d", ECWP_TABLE_CURRENCY, $currency_id)
        );

        if (!$currency_data) {
            return new \WP_Error('currency_not_found', 'Currency not found', array('status' => 404));
        }

        return rest_ensure_response($currency_data);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function get_vat(\WP_REST_Request $request)
    {
        global $wpdb;
        $vat_id = $request->get_param('id');
        $vat_data = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM %i WHERE id = %d", ECWP_TABLE_VATS, $vat_id));

        if (!$vat_data) {
            return new \WP_Error('vat_not_found', 'VAT not found', array('status' => 404));
        }

        return rest_ensure_response($vat_data);
    }

    /**
     * @return [type]
     */
    public function upload_logo()
    {
        if (isset($_SERVER['HTTP_X_WP_NONCE'])) {
            $nonce = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_WP_NONCE']));
            if (!wp_verify_nonce($nonce, 'wp_rest')) {
                return new \WP_Error('invalid_nonce', 'Nonce verification failed.');
            }
        } else {
            return new \WP_Error('missing_nonce', 'Nonce is missing.');
        }

        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        global $wp_filesystem;

        $upload_dir = wp_upload_dir();
        $upload_path = trailingslashit($upload_dir['basedir']) . 'logo/';

        if (!$wp_filesystem->is_dir($upload_path)) {
            $wp_filesystem->mkdir($upload_path);
        }

        if (!isset($_FILES['logo']) || !isset($_FILES['logo']['tmp_name']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            return new \WP_Error('file_upload_error', 'File upload error.');
        }

        $file = $_FILES['logo'];

        $file_type = wp_check_filetype($file['name']);
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        if (!in_array($file_type['ext'], $allowed_types)) {
            return new \WP_REST_Response(array('message' => 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.'), 400);
        }

        $max_file_size = 2 * 1024 * 1024;
        if ($file['size'] > $max_file_size) {
            return new \WP_REST_Response(array('message' => 'File size exceeds the maximum allowed size of 2 MB.'), 400);
        }

        $upload_overrides = array(
            'test_form' => false,
            'upload_dir' => $upload_path,
        );
        $upload = wp_handle_upload($file, $upload_overrides);

        if (isset($upload['error'])) {
            return new \WP_REST_Response(array('message' => 'Failed to save image: ' . $upload['error']), 500);
        }

        return rest_ensure_response(array('url' => $upload['url'], 'path' => $upload['file']));
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function add_currency(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $params = $request->get_json_params();
        $name = sanitize_text_field($params['name']);
        $symbol = sanitize_text_field($params['symbol']);
        $code = sanitize_text_field($params['code']);

        $result = $wpdb->insert(
            ECWP_TABLE_CURRENCY,
            [
                'name' => $name,
                'symbol' => $symbol,
                'code' => $code,
            ]
        );

        if ($result === false) {
            return new \WP_Error('currency_creation_failed', 'Failed to create currency', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $wpdb->insert_id, 'name' => $name, 'symbol' => $symbol, 'code' => $code], 201);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    function edit_currency(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $params = $request->get_json_params();
        $id = (int) $request['id'];
        $name = sanitize_text_field($params['name']);
        $symbol = sanitize_text_field($params['symbol']);
        $code = sanitize_text_field($params['code']);

        $result = $wpdb->update(
            ECWP_TABLE_CURRENCY,
            [
                'name' => $name,
                'symbol' => $symbol,
                'code' => $code,
            ],
            ['id' => $id]
        );

        if ($result === false) {
            return new \WP_Error('currency_update_failed', 'Failed to update currency', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $id, 'name' => $name, 'symbol' => $symbol, 'code' => $code], 200);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    function delete_currency(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $id = (int) $request['id'];

        $result = $wpdb->delete(
            ECWP_TABLE_CURRENCY,
            ['id' => $id]
        );

        if ($result === false) {
            return new \WP_Error('currency_deletion_failed', 'Failed to delete currency', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $id], 200);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    function delete_article(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $id = (int) $request['id'];

        $result = $wpdb->delete(
            ECWP_TABLE_ARTICLES,
            ['id' => $id]
        );

        if ($result === false) {
            return new \WP_Error('article_deletion_failed', 'Failed to delete article', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $id], 200);
    }
    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    function delete_category(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $id = (int) $request['id'];

        $result = $wpdb->delete(
            ECWP_TABLE_ARTICLES_CATEGORIES,
            ['id' => $id]
        );

        if ($result === false) {
            return new \WP_Error('article_deletion_failed', 'Failed to delete article', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $id], 200);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function add_vat(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $params = $request->get_json_params();
        $description = sanitize_text_field($params['description']);
        $rate = floatval($params['rate']);

        $result = $wpdb->insert(
            ECWP_TABLE_VATS,
            [
                'description' => $description,
                'rate' => $rate,
            ]
        );

        if ($result === false) {
            return new \WP_Error('vat_creation_failed', 'Failed to create VAT', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $wpdb->insert_id, 'description' => $description, 'rate' => $rate], 201);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function edit_vat(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $params = $request->get_json_params();
        $id = (int) $request['id'];
        $description = sanitize_text_field($params['description']);
        $rate = floatval($params['rate']);

        $result = $wpdb->update(
            ECWP_TABLE_VATS,
            [
                'description' => $description,
                'rate' => $rate,
            ],
            ['id' => $id]
        );

        if ($result === false) {
            return new \WP_Error('vat_update_failed', 'Failed to update VAT', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $id, 'description' => $description, 'rate' => $rate], 200);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function delete_vat(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $id = (int) $request['id'];

        $result = $wpdb->delete(
            ECWP_TABLE_VATS,
            ['id' => $id]
        );

        if ($result === false) {
            return new \WP_Error('vat_deletion_failed', 'Failed to delete VAT', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $id], 200);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function add_expenses_categories(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $params = $request->get_json_params();
        $name = sanitize_text_field($params['name']);

        $result = $wpdb->insert(
            ECWP_TABLE_EXPENSES_CATEGORIES,
            [
                'name' => $name,
            ]
        );

        if ($result === false) {
            return new \WP_Error('vat_creation_failed', 'Failed to create VAT', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $wpdb->insert_id, 'name' => $name], 201);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function edit_expenses_categories(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $params = $request->get_json_params();
        $id = (int) $request['id'];
        $name = sanitize_text_field($params['name']);

        $result = $wpdb->update(
            ECWP_TABLE_EXPENSES_CATEGORIES,
            [
                'name' => $name,
            ],
            ['id' => $id]
        );

        if ($result === false) {
            return new \WP_Error('expense_update_failed', 'Failed to update Expense category', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $id, 'name' => $name], 200);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function delete_expenses_categories(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $id = (int) $request['id'];

        $result = $wpdb->delete(
            ECWP_TABLE_EXPENSES_CATEGORIES,
            ['id' => $id]
        );

        if ($result === false) {
            return new \WP_Error('expense_deletion_failed', 'Failed to delete expense category', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $id], 200);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function add_payment_method(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $params = $request->get_json_params();
        $method_name = sanitize_text_field($params['method_name']);

        $result = $wpdb->insert(
            ECWP_TABLE_PAYMENTS_METHODS,
            [
                'method_name' => $method_name,
            ]
        );

        if ($result === false) {
            return new \WP_Error('method_payment_creation_failed', 'Failed to create Payment method', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $wpdb->insert_id, 'method_name' => $method_name], 201);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function edit_payment_method(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $params = $request->get_json_params();
        $id = (int) $request['id'];
        $method_name = sanitize_text_field($params['method_name']);

        $result = $wpdb->update(
            ECWP_TABLE_PAYMENTS_METHODS,
            [
                'method_name' => $method_name,
            ],
            ['id' => $id]
        );

        if ($result === false) {
            return new \WP_Error('payment_update_failed', 'Failed to update Payment method', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $id, 'method_name' => $method_name], 200);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function delete_payment_method(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $id = (int) $request['id'];

        $result = $wpdb->delete(
            ECWP_TABLE_PAYMENTS_METHODS,
            ['id' => $id]
        );

        if ($result === false) {
            return new \WP_Error('payment_deletion_failed', 'Failed to delete Payment method', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $id], 200);
    }

    public function get_setting($meta_key)
    {
        global $wpdb;
        $meta_value = $wpdb->get_var(
            $wpdb->prepare("SELECT meta_value FROM %i WHERE meta_key = %s", ECWP_TABLE_SETTINGS, $meta_key)
        );
        return $meta_value;
    }

    public function validate_license(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        $license_key = sanitize_text_field($request->get_param('license_key'));
        $response = wp_remote_post(ECWP_URL_LICENSE . '/wp-json/mlz-license/v1/check', [
            'body' => wp_json_encode(['license_key' => $license_key, 'domain' => sanitize_text_field($_SERVER['SERVER_NAME'])]),
            'headers' => ['Content-Type' => 'application/json'],
        ]);

        if (is_wp_error($response)) {
            return new \WP_REST_Response(['valid' => false, 'message' => 'Failed to validate license'], 500);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return new \WP_REST_Response($body, $response['response']['code']);
    }

    public function store_license(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        $license_key = sanitize_text_field($request->get_param('license_key'));
        $license_data = $request->get_param('license_data');

        $encrypted_license_key = openssl_encrypt($license_key, 'AES-128-ECB', ECWP_SECRET_KEY);
        update_option('ecwp_client_license_key', $encrypted_license_key);
        update_option('ecwp_client_license_data', $license_data);

        return new \WP_REST_Response(['success' => true, 'message' => 'License stored successfully'], 200);
    }

    public function check_license(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }

        $encrypted_license_key = get_option('ecwp_client_license_key');
        $license_data = get_option('ecwp_client_license_data');

        if (empty($encrypted_license_key) || empty($license_data)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'License not found.'], 404);
        }

        $license_key = openssl_decrypt($encrypted_license_key, 'AES-128-ECB', ECWP_SECRET_KEY);

        $license_data = $this->get_validate_license($license_key);

        $installed_plugins = get_plugins();
        $installed_versions = array();

        foreach ($installed_plugins as $plugin_path => $plugin_data) {
            $plugin_slug = dirname(plugin_basename($plugin_path));
            $installed_versions[$plugin_slug] = $plugin_data['Version'];
        }

        $license_plugins = $license_data['plugins'];
        $comparison_result = array();

        foreach ($license_plugins as $plugin_slug => $plugin_info) {
            if (isset($installed_versions[$plugin_slug])) {
                $comparison_result[$plugin_slug] = true;
            } else {
                $comparison_result[$plugin_slug] = false;
            }
        }

        // Construct the response
        $response_data = [
            'success' => true,
            'valid' => true,
            'license_key' => $license_key,
            'license_data' => $license_data,
            'installed_versions' => $installed_versions,
        ];

        return new \WP_REST_Response($response_data, 200);
    }

    public function get_validate_license($license_key)
    {
        $response = wp_remote_post(ECWP_URL_LICENSE . '/wp-json/mlz-license/v1/check', [
            'body' => wp_json_encode(['license_key' => sanitize_text_field($license_key), 'domain' => sanitize_text_field($_SERVER['SERVER_NAME'])]),
            'headers' => ['Content-Type' => 'application/json'],
        ]);

        if (is_wp_error($response)) {
            return ['valid' => false, 'message' => 'Failed to validate license'];
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function delete_license($request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }

        delete_option('ecwp_client_license_key');
        delete_option('ecwp_client_license_data');

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('License data deleted successfully.', 'my-easy-compta'),
        ], 200);
    }

    function check_update_plugin(\WP_REST_Request $request)
    {
        $plugin_slug = $request->get_param('plugin_slug');
        $current_version = $request->get_param('current_version');

        $api_url = ECWP_URL_LICENSE . '/wp-json/mlz-license/v1/check-update';

        $response = wp_remote_post($api_url, array(
            'body' => wp_json_encode(array(
                'plugin_slug' => $plugin_slug,
                'current_version' => $current_version,
            )),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
        ));

        if (is_wp_error($response)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Failed to check for updates.',
            ], 500);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return new \WP_REST_Response([
            'success' => true,
            'update_available' => $data['update_available'],
            'new_version' => $data['new_version'],
        ], 200);
    }

    public function download_update_plugin(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }

        $plugin_slug = sanitize_text_field($request->get_param('plugin_slug'));
        $site_url = site_url();
        $domain = trim(str_replace(array('http://', 'https://'), '', $site_url));

        $encrypted_license_key = get_option('ecwp_client_license_key');
        $license_key = openssl_decrypt($encrypted_license_key, 'AES-128-ECB', ECWP_SECRET_KEY);

        if (empty($plugin_slug)) {
            return new \WP_Error('invalid_parameters', 'Plugin slug or new version missing.', array('status' => 400));
        }

        $api_url = ECWP_URL_LICENSE . '/wp-json/mlz-license/v1/download-update';

        $response = wp_remote_post($api_url, array(
            'body' => array(
                'domain' => $domain,
                'plugin_slug' => $plugin_slug,
                'license_key' => $license_key,
            ),
        ));

        if (is_wp_error($response)) {
            return new \WP_Error('api_error', 'Failed to connect to update API.', array('status' => 500));
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || !isset($data['success'])) {
            return new \WP_Error('invalid_response', 'Invalid API response.', array('status' => 500));
        }

        if ($data['success']) {
            return new \WP_REST_Response($data, 200);
        } else {
            return new \WP_Error('download_failed', 'Failed to download update.', array('status' => 500));
        }
    }

}
