<?php namespace ECWP\Admin;

use ECWP\Admin\Settings\ECWP_Settings;
use ECWP\API\Routes;

class ECWP_APP
{
    protected $routes;

    public function __construct()
    {
        load_plugin_textdomain('my-easy-compta', false, ECWP_PATH . '/languages');
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        $this->routes = new Routes();
        $this->register_api_routes();
    }

    /**
     * @return [type]
     */
    public function add_admin_menu()
    {
        add_menu_page(__('myEasyCompta', 'my-easy-compta'),
            __('myEasyCompta', 'my-easy-compta'),
            'manage_options',
            'my-easy-compta',
            array($this, 'admin_page'),
            ECWP_URL . '/assets/img/icon.png',
            21);
        add_submenu_page('my-easy-compta',
            __('Dashboard', 'my-easy-compta'),
            __('Dashboard', 'my-easy-compta'),
            'manage_options',
            'my-easy-compta',
            array($this, 'admin_page'),
            1);
    }

    /**
     * @param mixed $hook
     *
     * @return [type]
     */
    public function enqueue_scripts($hook)
    {
        $pages = array('toplevel_page_my-easy-compta',
            'myeasycompta_page_my-easy-compta-clients',
            'myeasycompta_page_my-easy-compta-quotes',
            'myeasycompta_page_my-easy-compta-invoices',
            'myeasycompta_page_my-easy-compta-planning',
            'myeasycompta_page_my-easy-compta-payments',
            'myeasycompta_page_my-easy-compta-expenses',
            'myeasycompta_page_my-easy-compta-settings',
            'myeasycompta_page_my-easy-compta-addons',
            'dashboard_page_my-easy-compta-setup',
        );

        if (!in_array($hook, $pages)) {
            return;
        }

        wp_enqueue_style('my-easy-compta-admin-app-css', ECWP_URL . '/assets/dist/app.min.css', array(), ECWP_VERSION);
        wp_enqueue_style('my-easy-compta-admin-style', ECWP_URL . '/assets/dist/style.min.css', array(), ECWP_VERSION);
        wp_enqueue_style('fontawesome', ECWP_URL . '/assets/css/all.min.css', array(), ECWP_VERSION);
        wp_enqueue_script('my-easy-compta-admin', ECWP_URL . '/assets/dist/app.min.js', array(), ECWP_VERSION, true);
        wp_enqueue_script('my-easy-compta-custom', ECWP_URL . '/assets/js/custom.js', array(), ECWP_VERSION, true);
        wp_enqueue_script('chartjs', ECWP_URL . '/assets/js/chart.min.js', array(), ECWP_VERSION, true);
        add_filter('script_loader_tag', array($this, 'add_type_attribute'), 10, 2);
        require_once ECWP_PATH . '/languages/my-easy-compta-translations.php';
        wp_localize_script('my-easy-compta-admin', 'myEasyComptaAdmin', array('nonce' => wp_create_nonce('wp_rest'),
            'easyComptaTranslations' => $translations,
            'pluginUrl' => ECWP_URL,
            'LicenseCheckUrl' => 'https://myeasycompta.com/wp-json/mlz-license/v1/check',
        ));
    }

    /**
     * @param mixed $tag
     * @param mixed $handle
     *
     * @return [type]
     */
    public function add_type_attribute($tag, $handle)
    {
        $scripts = array(
            'my-easy-compta-admin',
            'my-easy-compta-clients',
            'my-easy-compta-quotes',
            'my-easy-compta-invoices',
            'my-easy-compta-payments',
            'my-easy-compta-expenses',
            'my-easy-compta-settings',
        );

        if (in_array($handle, $scripts)) {
            return str_replace(' src', ' type="module" src', $tag);
        }

        return $tag;
    }

    /**
     * @return [type]
     */
    public function admin_page()
    {
        echo '<div id="my-easy-compta-admin-app" class="ecwp-content"></div>';
    }

    /**
     * @return [type]
     */
    public function register_api_routes()
    {
        $this->routes->add_route('/stats/data', 'GET', $this, 'get_data_dashboard', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/stats/unpaid-invoices', 'GET', $this, 'get_unpaid_invoices', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/stats/expenses-month', 'GET', $this, 'get_expenses_current_month', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/stats/current-month-earnings', 'GET', $this, 'get_current_month_earnings', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/stats/total-earnings', 'GET', $this, 'get_total_earnings', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/stats/monthly-payments-expenses', 'GET', $this, 'get_monthly_payments_expenses', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/stats/recent-payments', 'GET', $this, 'get_recent_payments', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('/articles', 'GET', $this, 'get_articles', function () {
            return current_user_can('manage_options');
        });

        $this->routes->register_routes();
    }

    /**
     * @return [type]
     */
    public function get_unpaid_invoices()
    {
        global $wpdb;
        $unpaid_invoices = $wpdb->get_results(
            $wpdb->prepare("SELECT invoices.total_amount, clients.currency_id, currency.symbol
            FROM %i  AS invoices
            INNER JOIN %i AS clients ON invoices.client_id = clients.id
            INNER JOIN %i AS currency ON clients.currency_id = currency.id
            WHERE invoices.status = 'unpaid'",
                ECWP_TABLE_INVOICES, ECWP_TABLE_CLIENTS, ECWP_TABLE_CURRENCY),
            ARRAY_A
        );

        $unpaid_amounts_by_currency = array();

        if (!$unpaid_invoices) {
            $unpaid_amounts_by_currency[0]['total_amount'] = 0;
            return rest_ensure_response($unpaid_amounts_by_currency);
        }

        foreach ($unpaid_invoices as $invoice) {
            $currency = $invoice['currency_id'];
            $symbol = $invoice['symbol'];
            $amount = floatval($invoice['total_amount']);

            if (!isset($unpaid_amounts_by_currency[$currency])) {
                $unpaid_amounts_by_currency[$currency] = array(
                    'total_amount' => 0,
                    'symbol' => $symbol,
                );
            }
            $unpaid_amounts_by_currency[$currency]['total_amount'] += $amount;
        }
        foreach ($unpaid_amounts_by_currency as $currency => &$p) {
            $p['total_amount'] = number_format($p['total_amount'] ?? 0, 2, '.', ' ');
        }

        return rest_ensure_response($unpaid_amounts_by_currency);
    }

    /**
     * @return [type]
     */
    public function get_expenses_current_month()
    {
        global $wpdb;
        $current_time = current_time('mysql', false);
        $current_month_start = gmdate('Y-m-01', strtotime($current_time));
        $current_month_end = gmdate('Y-m-t', strtotime($current_time));
        $current_month_expenses = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT SUM(e.amount) as total_expenses,
                c.symbol as default_currency
         FROM %i AS e
         INNER JOIN %i AS c
             ON c.id = (
                SELECT meta_value
                FROM %i
                WHERE meta_key = 'default_currency'
             )
         WHERE e.expense_date >= %s AND e.expense_date <= %s",
                ECWP_TABLE_EXPENSES,
                ECWP_TABLE_CURRENCY,
                ECWP_TABLE_SETTINGS,
                $current_month_start,
                $current_month_end
            ),
            ARRAY_A
        );

        if (!$current_month_expenses) {
            return new \WP_Error('no_expenses_found', 'Aucune dépense trouvée pour le mois en cours', array('status' => 404));
        }

        $total_expenses = $current_month_expenses[0]['total_expenses'];
        $default_currency = $current_month_expenses[0]['default_currency'];
        $formatted_total_expenses = number_format($total_expenses ?? 0, 2, '.', ' ');
        return rest_ensure_response(array(
            'total_expenses' => $formatted_total_expenses,
            'default_currency' => $default_currency,
        ));
    }

    /**
     * @return [type]
     */
    public function get_current_month_earnings()
    {
        global $wpdb;
        $current_time = current_time('mysql', false);
        $current_month_start = gmdate('Y-m-01', strtotime($current_time));
        $current_month_end = gmdate('Y-m-t', strtotime($current_time));

        $settings = new ECWP_Settings();
        $default_currency_id = $settings->get_setting('default_currency');

        if (!$default_currency_id) {
            return new \WP_Error('default_currency_not_found', 'Devise par défaut non trouvée dans les paramètres', array('status' => 500));
        }

        $default_currency_symbol = $wpdb->get_var($wpdb->prepare(
            "SELECT symbol FROM %i WHERE id = %d",
            ECWP_TABLE_CURRENCY, $default_currency_id)
        );

        if (!$default_currency_symbol) {
            return new \WP_Error('default_currency_symbol_not_found', 'Symbole de la devise par défaut non trouvé dans la table des devises', array('status' => 500));
        }

        $current_month_earnings = $wpdb->get_results(
            $wpdb->prepare("SELECT SUM(payments.amount) as total_earnings
        FROM %i AS payments
        WHERE payments.payment_date >= %s
        AND payments.payment_date <= %s",
                ECWP_TABLE_PAYMENTS, $current_month_start, $current_month_end),
            ARRAY_A
        );

        $total = number_format($current_month_earnings[0]['total_earnings'] ?? 0, 2, '.', ' ');

        return rest_ensure_response(array('total' => $total, 'default_currency_symbol' => $default_currency_symbol));
    }

    /**
     *
     * @return [type]
     */
    public function get_total_earnings()
    {
        global $wpdb;
        $total_earnings = $wpdb->get_results(
            $wpdb->prepare("SELECT SUM(payments.amount) as total_earnings
        FROM %i AS payments",
                ECWP_TABLE_PAYMENTS),
            ARRAY_A
        );

        if (empty($total_earnings)) {
            return new \WP_Error('no_earnings_found', 'Aucun revenu trouvé', array('status' => 404));
        }

        $settings = new ECWP_Settings();
        $default_currency_id = $settings->get_setting('default_currency');

        if (!$default_currency_id) {
            return new \WP_Error('default_currency_not_found', 'Devise par défaut non trouvée dans les paramètres', array('status' => 500));
        }
        $default_currency_symbol = $wpdb->get_var($wpdb->prepare(
            "SELECT symbol FROM %i WHERE id = %d",
            ECWP_TABLE_CURRENCY, $default_currency_id
        ));

        if (!$default_currency_symbol) {
            return new \WP_Error('default_currency_symbol_not_found', 'Symbole de la devise par défaut non trouvé dans la table des devises', array('status' => 500));
        }
        $total = number_format($total_earnings[0]['total_earnings'] ?? 0, 2, '.', ' ');

        return rest_ensure_response(array('default_currency_id' => $default_currency_id, 'total' => $total, 'default_currency_symbol' => $default_currency_symbol));
    }

    /**
     * @return [type]
     */
    public function get_monthly_payments_expenses()
    {
        global $wpdb;
        $current_year = gmdate('Y');
        $payments = $wpdb->get_results(
            $wpdb->prepare("SELECT MONTH(payment_date) as month, SUM(amount) as total FROM %i
                WHERE YEAR(payment_date)=%d GROUP BY MONTH(payment_date)",
                ECWP_TABLE_PAYMENTS, $current_year),
            ARRAY_A);

        $expenses = $wpdb->get_results(
            $wpdb->prepare("SELECT MONTH(expense_date) as month, SUM(amount) as total FROM %i
                WHERE YEAR(expense_date)=%d GROUP BY MONTH(expense_date)",
                ECWP_TABLE_EXPENSES, $current_year),
            ARRAY_A);
        $months = [1 => __('January', 'my-easy-compta'),
            2 => __('February', 'my-easy-compta'),
            3 => __('March', 'my-easy-compta'),
            4 => __('April', 'my-easy-compta'),
            5 => __('May', 'my-easy-compta'),
            6 => __('June', 'my-easy-compta'),
            7 => __('July', 'my-easy-compta'),
            8 => __('August', 'my-easy-compta'),
            9 => __('September', 'my-easy-compta'),
            10 => __('October', 'my-easy-compta'),
            11 => __('November', 'my-easy-compta'),
            12 => __('December', 'my-easy-compta'),
        ];

        $monthly_data = ['months' => array_values($months),
            'payments' => array_fill(0, 12, 0),
            'expenses' => array_fill(0, 12, 0),
        ];

        foreach ($payments as $payment) {
            $monthly_data['payments'][$payment['month'] - 1] = floatval($payment['total']);
        }

        foreach ($expenses as $expense) {
            $monthly_data['expenses'][$expense['month'] - 1] = floatval($expense['total']);
        }

        return rest_ensure_response($monthly_data);
    }

    /**
     * @return [type]
     */
    public function get_recent_payments()
    {
        global $wpdb;
        $recent_payments = $wpdb->get_results(
            $wpdb->prepare("SELECT invoices.invoice_number,
                invoices.total_amount,
                payments_m.method_name,
                clients.currency_id,
                currency.symbol
            FROM %i AS payments
            INNER JOIN %i AS invoices ON payments.invoice_id = invoices.id
            INNER JOIN %i AS payments_m ON payments.payment_method_id = payments_m.id
            INNER JOIN %i AS clients ON invoices.client_id = clients.id
            INNER JOIN %i AS currency ON clients.currency_id = currency.id
            ORDER BY payments.payment_date DESC
            LIMIT 10",
                ECWP_TABLE_PAYMENTS, ECWP_TABLE_INVOICES, ECWP_TABLE_PAYMENTS_METHODS, ECWP_TABLE_CLIENTS, ECWP_TABLE_CURRENCY),
            ARRAY_A);

        if (!$recent_payments) {
            return new \WP_Error('no_payments', 'No payments found', array('status' => 404));
        }

        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        foreach ($recent_payments as &$result) {
            if (isset($result['invoice_number'])) {
                $result['invoice_number'] = $encrypt->decrypt($result['invoice_number']);
            }
            if (isset($result['total_amount'])) {
                $result['total_amount'] = $encrypt->decrypt($result['total_amount']);
            }
        }

        return rest_ensure_response($recent_payments);
    }

    public function get_articles(\WP_REST_Request $request)
    {
        global $wpdb;
        $search = $request->get_param('search');
        $method = $request->get_param('method');
        if ($method == 'name') {
            $results = $wpdb->get_results(
                $wpdb->prepare("SELECT name, ref, description, unit_price FROM %i WHERE name LIKE %s LIMIT 10", ECWP_TABLE_ARTICLES, '%' . $wpdb->esc_like($search) . '%')
            );
        } else {
            $results = $wpdb->get_results(
                $wpdb->prepare("SELECT name, ref, description, unit_price FROM %i WHERE ref LIKE %s LIMIT 10", ECWP_TABLE_ARTICLES, '%' . $wpdb->esc_like($search) . '%')
            );
        }

        return new \WP_REST_Response($results, 200);
    }

}
