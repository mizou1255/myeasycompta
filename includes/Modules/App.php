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
        //add_action('admin_notices', array($this, 'my_easy_compta_admin_notification'));
        //add_action('wp_ajax_my_easy_compta_admin_notification_hide', array($this, 'my_easy_compta_admin_notification_hide'));
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
            'myeasycompta_page_my-easy-compta-credits',
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
        wp_enqueue_script('my-easy-compta-custom', ECWP_URL . '/assets/js/notif-ads.js', array(), ECWP_VERSION, true);
        wp_enqueue_script('chartjs', ECWP_URL . '/assets/js/chart.min.js', array(), ECWP_VERSION, true);
        add_filter('script_loader_tag', array($this, 'add_type_attribute'), 10, 2);
        require_once ECWP_PATH . '/languages/my-easy-compta-translations.php';
        wp_localize_script('my-easy-compta-admin', 'myEasyComptaAdmin', array('nonce' => wp_create_nonce('wp_rest'),
            'easyComptaTranslations' => $translations,
            'pluginUrl' => ECWP_URL,
            'LicenseCheckUrl' => ECWP_URL_LICENSE . '/wp-json/mlz-license/v1/check',
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
            'my-easy-compta-credits',
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
        $this->routes->add_route('/stats/total-pogress-earnings', 'GET', $this, 'get_total_earnings_progess', function () {
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

        $this->routes->add_route('/search', 'GET', $this, 'global_search', function () {
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
        $invoices_table = ECWP_TABLE_INVOICES;
        $clients_table = ECWP_TABLE_CLIENTS;
        $currencies_table = ECWP_TABLE_CURRENCY;
        $unpaid_invoices = $wpdb->get_results(
            "SELECT invoices.total_amount, clients.currency_id, currency.symbol
            FROM {$invoices_table} AS invoices
            INNER JOIN {$clients_table} AS clients ON invoices.client_id = clients.id
            INNER JOIN {$currencies_table} AS currency ON clients.currency_id = currency.id
            WHERE invoices.status_stats = 'unpaid'",
            ARRAY_A
        );

        $unpaid_amounts_by_currency = array();
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt;

        if (!$unpaid_invoices) {
            $unpaid_amounts_by_currency[0]['total_amount'] = 0;
            return rest_ensure_response($unpaid_amounts_by_currency);
        }

        foreach ($unpaid_invoices as $invoice) {
            $currency = $invoice['currency_id'];
            $symbol = $invoice['symbol'];
            $amount = floatval($encrypt->decrypt($invoice['total_amount']));

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
        $expenses_table = ECWP_TABLE_EXPENSES;
        $currencies_table = ECWP_TABLE_CURRENCY;
        $settings_table = ECWP_TABLE_SETTINGS;
        $current_month_expenses = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT SUM(e.amount) as total_expenses,
                c.symbol as default_currency
         FROM {$expenses_table} AS e
         INNER JOIN {$currencies_table} AS c
             ON c.id = (
                SELECT meta_value
                FROM {$settings_table}
                WHERE meta_key = 'default_currency'
             )
         WHERE e.expense_date >= %s AND e.expense_date <= %s",
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

        $currencies_table = ECWP_TABLE_CURRENCY;
        $default_currency_symbol = $wpdb->get_var($wpdb->prepare(
            "SELECT symbol FROM {$currencies_table} WHERE id = %d",
            $default_currency_id)
        );

        if (!$default_currency_symbol) {
            return new \WP_Error('default_currency_symbol_not_found', 'Symbole de la devise par défaut non trouvé dans la table des devises', array('status' => 500));
        }

        $payments_table = ECWP_TABLE_PAYMENTS;
        $current_month_earnings = $wpdb->get_results(
            $wpdb->prepare("SELECT SUM(payments.amount) as total_earnings
        FROM {$payments_table} AS payments
        WHERE payments.payment_date >= %s
        AND payments.payment_date <= %s",
                $current_month_start, $current_month_end),
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
        $payments_table = ECWP_TABLE_PAYMENTS;
        $total_earnings = $wpdb->get_results(
            "SELECT SUM(payments.amount) as total_earnings
        FROM {$payments_table} AS payments",
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
        $currencies_table = ECWP_TABLE_CURRENCY;
        $default_currency_symbol = $wpdb->get_var($wpdb->prepare(
            "SELECT symbol FROM {$currencies_table} WHERE id = %d",
            $default_currency_id
        ));

        if (!$default_currency_symbol) {
            return new \WP_Error('default_currency_symbol_not_found', 'Symbole de la devise par défaut non trouvé dans la table des devises', array('status' => 500));
        }
        $total = number_format($total_earnings[0]['total_earnings'] ?? 0, 2, '.', ' ');

        return rest_ensure_response(array('default_currency_id' => $default_currency_id, 'total' => $total, 'default_currency_symbol' => $default_currency_symbol));
    }

    /**
     *
     * @return [type]
     */
    public function get_total_earnings_progess()
    {
        global $wpdb;
        $payments_table = ECWP_TABLE_PAYMENTS;
        $total_earnings = $wpdb->get_var(
            "SELECT SUM(payments.amount) as total_earnings
        FROM {$payments_table} AS payments"
        );

        if (empty($total_earnings)) {
            $total_earnings = 0;
        }

        return rest_ensure_response($total_earnings);
    }

    /**
     * @return [type]
     */
    public function get_monthly_payments_expenses()
    {
        global $wpdb;
        $current_year = gmdate('Y');
        $payments_table = ECWP_TABLE_PAYMENTS;
        $expenses_table = ECWP_TABLE_EXPENSES;
        $payments = $wpdb->get_results(
            $wpdb->prepare("SELECT MONTH(payment_date) as month, SUM(amount) as total FROM {$payments_table}
                WHERE YEAR(payment_date)=%d GROUP BY MONTH(payment_date)",
                $current_year),
            ARRAY_A);

        $expenses = $wpdb->get_results(
            $wpdb->prepare("SELECT MONTH(expense_date) as month, SUM(amount) as total FROM {$expenses_table}
                WHERE YEAR(expense_date)=%d GROUP BY MONTH(expense_date)",
                $current_year),
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
            FROM {$payments_table} AS payments
            INNER JOIN {$invoices_table} AS invoices ON payments.invoice_id = invoices.id
            INNER JOIN {$payments_methods_table} AS payments_m ON payments.payment_method_id = payments_m.id
            INNER JOIN {$clients_table} AS clients ON invoices.client_id = clients.id
            INNER JOIN {$currencies_table} AS currency ON clients.currency_id = currency.id
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
        $articles_table = ECWP_TABLE_ARTICLES;
        if ($method == 'name') {
            $results = $wpdb->get_results(
                $wpdb->prepare("SELECT name, ref, description, unit_price FROM {$articles_table} WHERE name LIKE %s LIMIT 10", '%' . $wpdb->esc_like($search) . '%')
            );
        } else {
            $results = $wpdb->get_results(
                $wpdb->prepare("SELECT name, ref, description, unit_price FROM {$articles_table} WHERE ref LIKE %s LIMIT 10", '%' . $wpdb->esc_like($search) . '%')
            );
        }

        do_action_ref_array('externe_article_results', [ &$results, $search]);

        return new \WP_REST_Response($results, 200);
    }

    public function my_easy_compta_admin_notification()
    {
        $allowed_pages = array(
            'toplevel_page_my-easy-compta',
            'myeasycompta_page_my-easy-compta-clients',
            'myeasycompta_page_my-easy-compta-quotes',
            'myeasycompta_page_my-easy-compta-invoices',
            'myeasycompta_page_my-easy-compta-planning',
            'myeasycompta_page_my-easy-compta-credits',
            'myeasycompta_page_my-easy-compta-payments',
            'myeasycompta_page_my-easy-compta-expenses',
            'myeasycompta_page_my-easy-compta-settings',
            'myeasycompta_page_my-easy-compta-addons',
            'dashboard_page_my-easy-compta-setup',
        );

        $current_screen = get_current_screen();

        if (!in_array($current_screen->id, $allowed_pages)) {
            return;
        }

        if (get_user_meta(get_current_user_id(), 'my_easy_compta_banner_dismissed', true)) {
            return;
        }

        if (isset($_COOKIE['my_easy_compta_banner_closed'])) {
            return;
        }
        ?>
    <div class="my-easy-compta-banner-container">
        <div class="my-easy-compta-banner-content">
            <div class="my-easy-compta-banner-text">
                <h3>🎉 <?php esc_html_e('Black Friday Sale - 30% OFF!', 'my-easy-compta');?> 🎉</h3>
                <p><?php esc_html_e('Upgrade to myEasyCompta PREMIUM now and enjoy 30% off with the promo code', 'my-easy-compta');?>
                    <strong>BF</strong>. <?php esc_html_e('Offer valid until November 30!', 'my-easy-compta');?></p>

                <a href="https://myeasycompta.com/produit/myeasycompta-premium/" target="_blank" class="my-easy-compta-banner-button">
                    <?php esc_html_e('Claim Your Discount', 'my-easy-compta');?>
                </a>
            </div>
            <div class="my-easy-compta-banner-never-show">
                <a href="#">
                    <?php esc_html_e('Close & never show again', 'my-easy-compta');?>
                </a>
            </div>
        </div>
        <div class="my-easy-compta-banner-close">
            <button type="button" class="my-easy-compta-banner-close">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
    </div>
    <?php
}

    public function my_easy_compta_admin_notification_hide()
    {
        if (isset($_POST['never_show'])) {
            update_user_meta(get_current_user_id(), 'my_easy_compta_banner_dismissed', true);
        }
        wp_die();
    }

    /**
     * Recherche globale dans factures, devis et clients
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function global_search(\WP_REST_Request $request)
    {
        global $wpdb;
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        
        $query = sanitize_text_field($request->get_param('q') ?? '');
        $type = sanitize_text_field($request->get_param('type') ?? 'all');
        
        // Si la requête est vide ET qu'aucun type spécifique n'est sélectionné, retourner un tableau vide
        // Sinon, permettre la recherche même sans requête pour afficher les résultats par défaut
        if (empty($query) && $type === 'all') {
            return rest_ensure_response([
                'results' => [],
                'total' => 0
            ]);
        }
        
        $results = [];
        
        // Recherche dans les factures
        if ($type === 'all' || $type === 'invoices') {
            $invoices_table = ECWP_TABLE_INVOICES;
            $clients_table = ECWP_TABLE_CLIENTS;
            
            $invoices_query = "SELECT 
                i.id,
                i.invoice_number,
                i.total_amount,
                i.status,
                i.created_at,
                c.company_name as client_name
            FROM {$invoices_table} i
            LEFT JOIN {$clients_table} c ON i.client_id = c.id
            WHERE 1=1";
            
            if (!empty($query)) {
                $invoices_query .= $wpdb->prepare(" AND (
                    i.invoice_number LIKE %s OR
                    c.company_name LIKE %s
                )", '%' . $wpdb->esc_like($query) . '%', '%' . $wpdb->esc_like($query) . '%');
            }
            
            $invoices_query .= " ORDER BY i.created_at DESC LIMIT 10";
            
            $invoices = $wpdb->get_results($invoices_query, OBJECT);
            
            foreach ($invoices as $invoice) {
                $invoice_number = $encrypt->decrypt($invoice->invoice_number);
                $total_amount = floatval($encrypt->decrypt($invoice->total_amount));
                
                $results[] = [
                    'type' => 'invoice',
                    'typeLabel' => 'Facture',
                    'id' => $invoice->id,
                    'title' => $invoice_number,
                    'description' => sprintf('Client: %s', $invoice->client_name ?? 'N/A'),
                    'date' => $invoice->created_at,
                    'amount' => $total_amount,
                    'status' => $encrypt->decrypt($invoice->status),
                    'statusLabel' => $this->getStatusLabel($encrypt->decrypt($invoice->status), 'invoice')
                ];
            }
        }
        
        // Recherche dans les devis
        if ($type === 'all' || $type === 'quotes') {
            $quotes_table = ECWP_TABLE_QUOTES;
            $clients_table = ECWP_TABLE_CLIENTS;
            
            $quotes_query = "SELECT 
                q.id,
                q.quote_number,
                q.total_amount,
                q.status,
                q.created_at,
                c.company_name as client_name
            FROM {$quotes_table} q
            LEFT JOIN {$clients_table} c ON q.client_id = c.id
            WHERE 1=1";
            
            if (!empty($query)) {
                $quotes_query .= $wpdb->prepare(" AND (
                    q.quote_number LIKE %s OR
                    c.company_name LIKE %s
                )", '%' . $wpdb->esc_like($query) . '%', '%' . $wpdb->esc_like($query) . '%');
            }
            
            $quotes_query .= " ORDER BY q.created_at DESC LIMIT 10";
            
            $quotes = $wpdb->get_results($quotes_query, OBJECT);
            
            foreach ($quotes as $quote) {
                $quote_number = $encrypt->decrypt($quote->quote_number);
                $total_amount = floatval($encrypt->decrypt($quote->total_amount));
                
                $results[] = [
                    'type' => 'quote',
                    'typeLabel' => 'Devis',
                    'id' => $quote->id,
                    'title' => $quote_number,
                    'description' => sprintf('Client: %s', $quote->client_name ?? 'N/A'),
                    'date' => $quote->created_at,
                    'amount' => $total_amount,
                    'status' => $encrypt->decrypt($quote->status),
                    'statusLabel' => $this->getStatusLabel($encrypt->decrypt($quote->status), 'quote')
                ];
            }
        }
        
        // Recherche dans les clients
        if ($type === 'all' || $type === 'clients') {
            $clients_table = ECWP_TABLE_CLIENTS;
            
            if (!empty($query)) {
                $like_query = '%' . $wpdb->esc_like($query) . '%';
                $clients = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT 
                            id,
                            company_name,
                            email,
                            manager_name,
                            phone
                        FROM {$clients_table}
                        WHERE company_name LIKE %s 
                           OR email LIKE %s 
                           OR manager_name LIKE %s
                           OR phone LIKE %s
                        ORDER BY company_name ASC 
                        LIMIT 10",
                        $like_query,
                        $like_query,
                        $like_query,
                        $like_query
                    ),
                    OBJECT
                );
            } else {
                // Si pas de requête, retourner les 10 derniers clients
                // La table clients n'a pas de champ created_at, on trie par id (plus récent = id plus élevé)
                $clients = $wpdb->get_results(
                    "SELECT 
                        id,
                        company_name,
                        email,
                        manager_name,
                        phone
                    FROM {$clients_table}
                    ORDER BY id DESC 
                    LIMIT 10",
                    OBJECT
                );
            }
            
            foreach ($clients as $client) {
                $description_parts = [];
                if (!empty($client->manager_name)) {
                    $description_parts[] = $client->manager_name;
                }
                if (!empty($client->email)) {
                    $description_parts[] = $client->email;
                }
                if (!empty($client->phone)) {
                    $description_parts[] = $client->phone;
                }
                
                $results[] = [
                    'type' => 'client',
                    'typeLabel' => 'Client',
                    'id' => $client->id,
                    'title' => $client->company_name ?? 'Sans nom',
                    'description' => !empty($description_parts) ? implode(' - ', $description_parts) : 'Pas d\'informations supplémentaires',
                    'date' => null, // Pas de date dans la table clients
                    'amount' => null,
                    'status' => null,
                    'statusLabel' => null
                ];
            }
        }
        
        // Recherche dans les paiements
        if ($type === 'all' || $type === 'payments') {
            $payments_table = ECWP_TABLE_PAYMENTS;
            $clients_table = ECWP_TABLE_CLIENTS;
            $invoices_table = ECWP_TABLE_INVOICES;
            
            $payments_query = "SELECT 
                p.id,
                p.amount,
                p.payment_date,
                c.company_name,
                i.invoice_number
            FROM {$payments_table} p
            LEFT JOIN {$clients_table} c ON p.client_id = c.id
            LEFT JOIN {$invoices_table} i ON p.invoice_id = i.id
            WHERE 1=1";
            
            if (!empty($query)) {
                $like_query = '%' . $wpdb->esc_like($query) . '%';
                $payments_query .= $wpdb->prepare(" AND (
                    c.company_name LIKE %s OR
                    i.invoice_number LIKE %s
                )", $like_query, $like_query);
            }
            
            $payments_query .= " ORDER BY p.payment_date DESC LIMIT 10";
            
            $payments = $wpdb->get_results($payments_query, OBJECT);
            
            foreach ($payments as $payment) {
                $invoice_number = $encrypt->decrypt($payment->invoice_number);
                $amount = floatval($payment->amount);
                
                $results[] = [
                    'type' => 'payment',
                    'typeLabel' => 'Paiement',
                    'id' => $payment->id,
                    'title' => 'Paiement - ' . ($payment->company_name ?? 'N/A'),
                    'description' => sprintf('Facture: %s - Montant: %s €', $invoice_number, number_format($amount, 2, ',', ' ')),
                    'date' => $payment->payment_date,
                    'amount' => $amount,
                    'status' => null,
                    'statusLabel' => null
                ];
            }
        }
        
        // Recherche dans les dépenses
        if ($type === 'all' || $type === 'expenses') {
            $expenses_table = ECWP_TABLE_EXPENSES;
            $clients_table = ECWP_TABLE_CLIENTS;
            $categories_table = ECWP_TABLE_EXPENSES_CATEGORIES;
            
            $expenses_query = "SELECT 
                e.id,
                e.amount,
                e.expense_date,
                e.notes,
                c.company_name,
                cat.name as category_name
            FROM {$expenses_table} e
            LEFT JOIN {$clients_table} c ON e.client_id = c.id
            LEFT JOIN {$categories_table} cat ON e.category_id = cat.id
            WHERE 1=1";
            
            if (!empty($query)) {
                $like_query = '%' . $wpdb->esc_like($query) . '%';
                $expenses_query .= $wpdb->prepare(" AND (
                    c.company_name LIKE %s OR
                    cat.name LIKE %s OR
                    e.notes LIKE %s
                )", $like_query, $like_query, $like_query);
            }
            
            $expenses_query .= " ORDER BY e.expense_date DESC LIMIT 10";
            
            $expenses = $wpdb->get_results($expenses_query, OBJECT);
            
            foreach ($expenses as $expense) {
                $amount = floatval($expense->amount);
                
                $results[] = [
                    'type' => 'expense',
                    'typeLabel' => 'Dépense',
                    'id' => $expense->id,
                    'title' => ($expense->category_name ?? 'Dépense') . ' - ' . ($expense->company_name ?? 'Sans client'),
                    'description' => sprintf('Montant: %s €', number_format($amount, 2, ',', ' ')) . ($expense->notes ? ' - ' . substr($expense->notes, 0, 50) : ''),
                    'date' => $expense->expense_date,
                    'amount' => $amount,
                    'status' => null,
                    'statusLabel' => null
                ];
            }
        }
        
        // Recherche dans les avoirs
        if ($type === 'all' || $type === 'credits') {
            $credits_table = ECWP_TABLE_CREDITS;
            $invoices_table = ECWP_TABLE_INVOICES;
            $clients_table = ECWP_TABLE_CLIENTS;
            
            $credits_query = "SELECT 
                c.id,
                c.credit_number,
                c.created_at,
                i.invoice_number,
                i.total_amount,
                cl.company_name
            FROM {$credits_table} c
            LEFT JOIN {$invoices_table} i ON c.invoice_id = i.id
            LEFT JOIN {$clients_table} cl ON i.client_id = cl.id
            WHERE 1=1";
            
            if (!empty($query)) {
                $like_query = '%' . $wpdb->esc_like($query) . '%';
                $credits_query .= $wpdb->prepare(" AND (
                    c.credit_number LIKE %s OR
                    i.invoice_number LIKE %s OR
                    cl.company_name LIKE %s
                )", $like_query, $like_query, $like_query);
            }
            
            $credits_query .= " ORDER BY c.created_at DESC LIMIT 10";
            
            $credits = $wpdb->get_results($credits_query, OBJECT);
            
            foreach ($credits as $credit) {
                $invoice_number = $encrypt->decrypt($credit->invoice_number);
                $total_amount = floatval($encrypt->decrypt($credit->total_amount));
                
                $results[] = [
                    'type' => 'credit',
                    'typeLabel' => 'Avoir',
                    'id' => $credit->id,
                    'title' => $credit->credit_number ?? 'Avoir #' . $credit->id,
                    'description' => sprintf('Facture: %s - Client: %s - Montant: %s €', $invoice_number, $credit->company_name ?? 'N/A', number_format($total_amount, 2, ',', ' ')),
                    'date' => $credit->created_at,
                    'amount' => $total_amount,
                    'status' => null,
                    'statusLabel' => null
                ];
            }
        }
        
        // Limiter à 20 résultats au total
        $results = array_slice($results, 0, 20);
        
        return rest_ensure_response([
            'results' => $results,
            'total' => count($results)
        ]);
    }
    
    /**
     * Retourne le label d'un statut
     */
    private function getStatusLabel($status, $type = 'invoice')
    {
        $labels = [
            'invoice' => [
                'paid' => 'Payée',
                'unpaid' => 'Impayée',
                'sent' => 'Envoyée',
                'draft' => 'Brouillon'
            ],
            'quote' => [
                'accepted' => 'Accepté',
                'pending' => 'En attente',
                'rejected' => 'Refusé',
                'draft' => 'Brouillon'
            ]
        ];
        
        return $labels[$type][$status] ?? $status;
    }

}
