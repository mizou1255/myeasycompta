<?php
namespace ECWP\Admin;

use ECWP\Admin\Settings\ECWP_Settings;
use ECWP\API\Routes;

class ECWP_APP
{
    protected $routes;

    public function __construct()
    {
        load_plugin_textdomain('my-easy-compta', false, ECWP_PATH . '/languages');
        add_action('admin_menu', array($this, 'add_admin_menu'), 9);
        add_action('admin_notices', array($this, 'notice_permalink_warning'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_head', array($this, 'enqueue_global_admin_styles'));

        $this->routes = new Routes();
        $this->register_api_routes();
    }

    /**
     * @return [type]
     */
    public function add_admin_menu()
    {
        // Menu parent
        add_menu_page(
            __('myEasyCompta', 'my-easy-compta'),
            __('myEasyCompta', 'my-easy-compta'),
            'manage_options',
            'my-easy-compta',
            array($this, 'admin_page'),
            'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgd2lkdGg9IjIwIiBoZWlnaHQ9IjIwIj48cmVjdCB4PSIzIiB5PSIxMyIgd2lkdGg9IjQiIGhlaWdodD0iNiIgcng9IjEiIGZpbGw9IiM5MzMzZWEiLz48cmVjdCB4PSI4IiB5PSI4IiB3aWR0aD0iNCIgaGVpZ2h0PSIxMSIgcng9IjEiIGZpbGw9IiM3YzNhZWQiLz48cmVjdCB4PSIxMyIgeT0iMTEiIHdpZHRoPSI0IiBoZWlnaHQ9IjgiIHJ4PSIxIiBmaWxsPSIjNjM2NmYxIi8+PHJlY3QgeD0iMTgiIHk9IjQiIHdpZHRoPSI0IiBoZWlnaHQ9IjE1IiByeD0iMSIgZmlsbD0iIzRmNDZlNSIvPjwvc3ZnPg==',
            21
        );

        // Submenu Dashboard (même slug que le parent pour être le premier item)
        add_submenu_page(
            'my-easy-compta',
            __('Dashboard', 'my-easy-compta'),
            __('Dashboard', 'my-easy-compta'),
            'manage_options',
            'my-easy-compta',
            array($this, 'admin_page'),
            0
        );
    }

    /**
     * @param mixed $hook
     *
     * @return [type]
     */
    public function enqueue_global_admin_styles()
    {
        echo '<style>#adminmenu .wp-submenu sup{background-color:gold;color:#000;border-radius:10px;padding:2px 5px;font-size:7px;font-weight:500;}</style>';

        // Confirmation avant désactivation du plugin
        $screen = get_current_screen();
        if ($screen && $screen->id === 'plugins') {
            echo '<style>
#ecwp-deactivate-overlay{display:none;position:fixed;inset:0;background:rgba(2,6,23,.85);backdrop-filter:blur(8px);z-index:999999;align-items:center;justify-content:center}
#ecwp-deactivate-overlay.open{display:flex}
#ecwp-deactivate-box{background:#fff;border-radius:16px;padding:32px;max-width:440px;width:90%;box-shadow:0 24px 80px rgba(0,0,0,.4);text-align:center}
#ecwp-deactivate-box h3{margin:0 0 8px;font-size:1.1rem;font-weight:800;color:#0f172a}
#ecwp-deactivate-box p{margin:0 0 24px;font-size:.9rem;color:#475569;line-height:1.6}
#ecwp-deactivate-box .ecwp-warn{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;margin-bottom:24px;font-size:.82rem;color:#b91c1c;font-weight:600}
.ecwp-btn-cancel{background:#f1f5f9;color:#334155;border:none;padding:10px 22px;border-radius:10px;font-weight:700;cursor:pointer;font-size:.875rem}
.ecwp-btn-cancel:hover{background:#e2e8f0}
.ecwp-btn-confirm{background:#ef4444;color:#fff;border:none;padding:10px 22px;border-radius:10px;font-weight:700;cursor:pointer;font-size:.875rem;margin-left:10px}
.ecwp-btn-confirm:hover{background:#dc2626}
</style>
<div id="ecwp-deactivate-overlay">
  <div id="ecwp-deactivate-box">
    <h3>⚠️ Désactiver myEasyCompta ?</h3>
    <p>Vous êtes sur le point de désactiver le plugin.</p>
    <div class="ecwp-warn">🗄️ Vos données (factures, devis, clients, paiements) sont <strong>conservées</strong> en base de données et seront à nouveau accessibles dès la réactivation du plugin.<br><br>⚠️ La suppression définitive des données n\'intervient qu\'en cas de <strong>suppression complète</strong> du plugin depuis l\'écran Extensions.</div>
    <div>
      <button class="ecwp-btn-cancel" onclick="document.getElementById(\'ecwp-deactivate-overlay\').classList.remove(\'open\')">Annuler</button>
      <button class="ecwp-btn-confirm" id="ecwp-confirm-deactivate">Désactiver quand même</button>
    </div>
  </div>
</div>
<script>
(function(){
  var deactivateUrl = null;
  document.addEventListener("DOMContentLoaded", function(){
    var links = document.querySelectorAll(\'tr[data-plugin="my-easy-compta/my-easy-compta.php"] .deactivate a\');
    links.forEach(function(link){
      link.addEventListener("click", function(e){
        e.preventDefault();
        deactivateUrl = link.href;
        document.getElementById("ecwp-deactivate-overlay").classList.add("open");
      });
    });
    document.getElementById("ecwp-confirm-deactivate").addEventListener("click", function(){
      if(deactivateUrl) window.location.href = deactivateUrl;
    });
    document.getElementById("ecwp-deactivate-overlay").addEventListener("click", function(e){
      if(e.target === this) this.classList.remove("open");
    });
  });
})();
</script>';
        }

        // Bloquer le scroll natif WP admin sur les pages du plugin (évite le double scrollbar)
        $screen = get_current_screen();
        $plugin_pages = array(
            'toplevel_page_my-easy-compta',
            'my-easy-compta_page_my-easy-compta',
            'myeasycompta_page_my-easy-compta',
        );
        if ($screen && in_array($screen->id, $plugin_pages)) {
            echo '<style>
html, body, #wpwrap, #wpcontent, #wpbody, #wpbody-content {
    overflow: hidden !important;
    height: 100% !important;
}
</style>';
        }
    }

    public function enqueue_scripts($hook)
    {
        $allowed_hooks = array(
            'toplevel_page_my-easy-compta',
            'my-easy-compta_page_my-easy-compta',
            'myeasycompta_page_my-easy-compta'
        );

        if (!in_array($hook, $allowed_hooks)) {
            return;
        }

        // FontAwesome CDN
        wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');

        $main_css_version = file_exists(ECWP_PATH . '/assets/css/main.css') ? filemtime(ECWP_PATH . '/assets/css/main.css') : ECWP_VERSION;
        $style_css_version = file_exists(ECWP_PATH . '/assets/dist/index.min.css') ? filemtime(ECWP_PATH . '/assets/dist/index.min.css') : ECWP_VERSION;
        $app_js_version = file_exists(ECWP_PATH . '/assets/dist/index.min.js') ? filemtime(ECWP_PATH . '/assets/dist/index.min.js') : ECWP_VERSION;

        // CSS principal
        wp_enqueue_style('my-easy-compta-main', ECWP_URL . '/assets/css/main.css', array(), $main_css_version);
        wp_enqueue_style('my-easy-compta-style', ECWP_URL . '/assets/dist/style.min.css', array(), $style_css_version);
        wp_enqueue_style('my-easy-compta-index', ECWP_URL . '/assets/dist/index.min.css', array(), $style_css_version);

        // Polyfill Node.js `process` for browser bundles (required by vue3-editor/quill)
        wp_add_inline_script('jquery', 'window.process = window.process || { env: { NODE_ENV: "production" }, versions: {} };');

        // JS principal
        wp_enqueue_script('my-easy-compta-admin', ECWP_URL . '/assets/dist/app.min.js', array('jquery'), $app_js_version, true);

        add_filter('script_loader_tag', array($this, 'add_type_attribute'), 10, 2);
        require_once ECWP_PATH . '/languages/my-easy-compta-translations.php';

        $license_data = get_option('ecwp_client_license_data');
        $license_valid = (is_array($license_data) && !empty($license_data['valid']));
        $export_addon_active = $this->is_addon_pro('my-easy-compta-export');

        wp_localize_script('my-easy-compta-admin', 'myEasyComptaAdmin', array(
            'nonce'          => wp_create_nonce('wp_rest'),
            'permalinksOk'   => $this->has_pretty_permalinks(),
            'permalinksUrl'  => admin_url('options-permalink.php'),
            'setupComplete'  => get_option('ecwp_setup_complete') === '1',
            'setupUrl'       => admin_url('index.php?page=my-easy-compta-setup'),
            'marketingBannerNonce' => wp_create_nonce('ecwp_marketing_banner_nonce'),
            'showMarketingBanner' => $this->should_show_marketing_banner(),
            'licenseValid' => $license_valid,
            'exportAddonActive' => $export_addon_active,
            'easyComptaTranslations' => $translations,
            'pluginUrl' => ECWP_URL,
            'LicenseCheckUrl' => ECWP_URL_LICENSE . '/wp-json/mec-license/v1/check',
            'addonsStatus' => array(
                'planning'      => $this->is_addon_active_by_prefix('my-easy-compta-planning'),
                'email'         => $this->is_addon_active_by_prefix('my-easy-compta-email'),
                'payment'       => $this->is_addon_active_by_prefix('my-easy-compta-payment'),
                'stats'         => $this->is_addon_active_by_prefix('my-easy-compta-stats'),
                'signature'     => $this->is_addon_active_by_prefix('my-easy-compta-signature'),
                'export'        => $this->is_addon_active_by_prefix('my-easy-compta-export'),
                'backup'        => $this->is_addon_active_by_prefix('my-easy-compta-backup'),
                'recurring'     => $this->is_addon_active_by_prefix('myeasycompta-recurring-invoices'),
                'advance'       => $this->is_addon_active_by_prefix('my-easy-compta-advance'),
                'siret'         => $this->is_addon_active_by_prefix('my-easy-compta-siret'),
                'qrcode_stripe' => $this->is_addon_active_by_prefix('my-easy-compta-qrcode-stripe'),
                'user'          => $this->is_addon_active_by_prefix('my-easy-compta-user'),
                'woo'           => $this->is_addon_active_by_prefix('my-easy-compta-woo'),
                'surecart'      => $this->is_addon_active_by_prefix('my-easy-compta-surecart'),
                'mobile'        => $this->is_addon_active_by_prefix('my-easy-compta-mobile'),
                'delivery'      => $this->is_addon_pro('my-easy-compta-delivery'),
                'online_quote'  => $this->is_addon_active_by_prefix('my-easy-compta-online-quote'),
                'sms'           => $this->is_addon_active_by_prefix('my-easy-compta-sms'),
            ),
            'deliveryAddonActive'    => $this->is_addon_pro('my-easy-compta-delivery'),
            'onlineQuoteAddonActive' => $this->is_addon_active_by_prefix('my-easy-compta-online-quote'),
            'smsAddonActive'         => $this->is_addon_active_by_prefix('my-easy-compta-sms'),
            'webhooksAddonActive'    => $this->is_addon_active_by_prefix('my-easy-compta-webhooks'),
            'fecAddonActive'         => $this->is_addon_active_by_prefix('my-easy-compta-fec'),
            'ocrAddonActive'         => $this->is_addon_active_by_prefix('my-easy-compta-ocr'),
            'pdpConfigured'          => $this->is_pdp_configured(),
        ));
    }

    /**
     * Vérifie si un PDP est configuré dans les réglages.
     */
    /**
     * Returns true when WordPress pretty-permalinks are enabled (REST API works).
     */
    private function has_pretty_permalinks(): bool
    {
        return (bool) get_option('permalink_structure');
    }

    /**
     * Shows an admin notice on myEasyCompta pages when permalinks are not configured.
     */
    public function notice_permalink_warning(): void
    {
        if ($this->has_pretty_permalinks()) return;

        $screen = get_current_screen();
        if (!$screen) return;

        $ecwp_screens = [
            'toplevel_page_my-easy-compta',
            'my-easy-compta_page_my-easy-compta',
            'myeasycompta_page_my-easy-compta',
            'dashboard_page_my-easy-compta-setup',
        ];
        if (!in_array($screen->id, $ecwp_screens, true)) return;

        $url = admin_url('options-permalink.php');
        printf(
            '<div class="notice notice-error" style="border-left-color:#dc2626;padding:16px 20px;">
                <p style="margin:0 0 8px;font-size:15px;font-weight:700;">⚠️ myEasyCompta — Action requise</p>
                <p style="margin:0 0 10px;">Les <strong>permaliens WordPress</strong> sont désactivés. myEasyCompta utilise l\'API REST de WordPress et ne fonctionne pas en mode <em>Plain</em>.</p>
                <a href="%s" class="button button-primary">⚙️ Configurer les permaliens</a>
                <span style="margin-left:12px;font-size:12px;color:#6b7280;">Réglages → Permaliens → choisissez n\'importe quelle option sauf "Plain", puis cliquez sur Enregistrer.</span>
            </div>',
            esc_url($url)
        );
    }

    private function is_pdp_configured(): int
    {
        if (has_filter('myeasycompta_pdp_transmit_handler')) {
            return 1;
        }
        global $wpdb;
        $pdp_active = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM " . ECWP_TABLE_SETTINGS . " WHERE meta_key = %s", 'pdp_active'
        ));
        return !empty($pdp_active) ? 1 : 0;
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
            // Replace existing type attribute if present, otherwise add type="module" before src
            if (str_contains($tag, "type='text/javascript'")) {
                $tag = str_replace("type='text/javascript'", 'type="module"', $tag);
            } elseif (str_contains($tag, 'type="text/javascript"')) {
                $tag = str_replace('type="text/javascript"', 'type="module"', $tag);
            } else {
                $tag = str_replace(' src', ' type="module" src', $tag);
            }
            return $tag;
        }

        return $tag;
    }

    /**
     * @return [type]
     */
    public function admin_page()
    {
        // Le modal "setup requis" est géré côté Vue (App.vue) via window.myEasyComptaAdmin.setupComplete.
        // On monte toujours l'app — le modal bloque l'UI si le setup n'est pas terminé.
        echo '<div id="my-easy-compta-admin-app" class="ecwp-content"></div>';
    }

    /**
     * Injecte le banner marketing dans le footer de toutes les pages myEasyCompta
     */
    public function inject_marketing_banner_footer()
    {
        if ($this->should_show_marketing_banner()) {
            echo $this->render_marketing_banner();
        }
    }

    private function is_ecwp_page(): bool
    {
        $page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';
        return (strpos($page, 'my-easy-compta') === 0);
    }

    private function is_addon_active_by_prefix(string $prefix): bool
    {
        $active_plugins = (array) get_option('active_plugins', array());
        foreach ($active_plugins as $plugin_file) {
            if (strpos((string) $plugin_file, $prefix . '/') === 0) {
                return true;
            }
        }
        return false;
    }

    private function is_addon_pro(string $slug): bool
    {
        if (!$this->is_addon_active_by_prefix($slug)) {
            return false;
        }

        $license_data = get_option('ecwp_client_license_data');
        if (!is_array($license_data)) {
            return false;
        }

        // New format: addons[] contains slugs directly (or '_bundle' for all)
        if (!empty($license_data['addons']) && is_array($license_data['addons'])) {
            if (in_array('_bundle', $license_data['addons'], true)) return true;
            // Strip prefix to get short slug (e.g. 'my-easy-compta-email' → 'email')
            $short = preg_replace('/^my-easy-compta-/', '', $slug);
            if (in_array($short, $license_data['addons'], true)) return true;
            if (in_array($slug, $license_data['addons'], true)) return true;
        }

        // Legacy format: plugins[] array of {product_slug}
        if (!empty($license_data['plugins']) && is_array($license_data['plugins'])) {
            foreach ($license_data['plugins'] as $p) {
                if (is_array($p) && ($slug === ($p['product_slug'] ?? ''))) return true;
            }
        }

        return false;
    }

    private function should_show_marketing_banner(): bool
    {
        if (!$this->is_ecwp_page()) {
            return false;
        }
        if (!current_user_can('manage_options')) {
            return false;
        }
        $userId = get_current_user_id();
        if ($userId <= 0) {
            return false;
        }
        $dismissedUntil = (int) get_user_meta($userId, 'ecwp_marketing_banner_dismissed_until', true);
        if ($dismissedUntil > time()) {
            return false;
        }
        return true;
    }

    private function render_marketing_banner(): string
    {
        if (!$this->should_show_marketing_banner()) {
            return '';
        }

        $proUrl = 'https://myeasycompta.com/addons';
        $addonsUrl = admin_url('admin.php?page=my-easy-compta-addons');

        $title = __('Passez à myEasyCompta Pro', 'my-easy-compta');
        $text = __('Débloquez des fonctionnalités avancées et accédez à nos add-ons pour aller plus loin.', 'my-easy-compta');
        $ctaPro = __('Découvrir Pro', 'my-easy-compta');
        $ctaAddons = __('Voir les add-ons', 'my-easy-compta');

        $html = '<div id="ecwp-marketing-banner" class="ecwp-marketing-banner" role="region" aria-label="myEasyCompta marketing">';
        $html .= '  <div class="ecwp-marketing-banner__arrow"></div>';
        $html .= '  <div class="ecwp-marketing-banner__header">';
        $html .= '    <div class="ecwp-marketing-banner__badge">PRO</div>';
        $html .= '    <button type="button" class="ecwp-marketing-banner__close" data-ecwp-marketing-close aria-label="' . esc_attr__('Fermer', 'my-easy-compta') . '">✕</button>';
        $html .= '  </div>';
        $html .= '  <div class="ecwp-marketing-banner__content">';
        $html .= '    <h3 class="ecwp-marketing-banner__title">' . esc_html($title) . '</h3>';
        $html .= '    <p class="ecwp-marketing-banner__text">' . esc_html($text) . '</p>';
        $html .= '    <ul class="ecwp-marketing-banner__features">';
        $html .= '      <li class="ecwp-marketing-banner__feature">' . esc_html__('Fonctionnalités illimitées', 'my-easy-compta') . '</li>';
        $html .= '      <li class="ecwp-marketing-banner__feature">' . esc_html__('Support prioritaire', 'my-easy-compta') . '</li>';
        $html .= '      <li class="ecwp-marketing-banner__feature">' . esc_html__('Add-ons premium', 'my-easy-compta') . '</li>';
        $html .= '    </ul>';
        $html .= '  </div>';
        $html .= '  <div class="ecwp-marketing-banner__actions">';
        $html .= '    <a class="ecwp-marketing-banner__btn ecwp-marketing-banner__btn--primary" href="' . esc_url($proUrl) . '" target="_blank" rel="noreferrer noopener">' . esc_html($ctaPro) . ' →</a>';
        $html .= '    <a class="ecwp-marketing-banner__btn ecwp-marketing-banner__btn--secondary" href="' . esc_url($addonsUrl) . '">' . esc_html($ctaAddons) . '</a>';
        $html .= '  </div>';
        $html .= '</div>';

        return $html;
    }

    public function ajax_dismiss_marketing_banner()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'forbidden'), 403);
        }
        $nonce = isset($_POST['nonce']) ? sanitize_text_field((string) $_POST['nonce']) : '';
        if (!wp_verify_nonce($nonce, 'ecwp_marketing_banner_nonce')) {
            wp_send_json_error(array('message' => 'invalid_nonce'), 403);
        }
        $userId = get_current_user_id();
        if ($userId <= 0) {
            wp_send_json_error(array('message' => 'no_user'), 400);
        }
        // Masquer pendant 7 jours
        update_user_meta($userId, 'ecwp_marketing_banner_dismissed_until', time() + (7 * DAY_IN_SECONDS));
        wp_send_json_success(array('success' => true));
    }

    /**
     * @return [type]
     */
    public function register_api_routes()
    {
        $this->routes->add_route('stats', 'GET', $this, 'get_stats_summary', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('stats/data', 'GET', $this, 'get_data_dashboard', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('stats/unpaid-invoices', 'GET', $this, 'get_unpaid_invoices', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('stats/expenses-month', 'GET', $this, 'get_expenses_current_month', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('stats/current-month-earnings', 'GET', $this, 'get_current_month_earnings', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('stats/total-earnings', 'GET', $this, 'get_total_earnings', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('stats/total-pogress-earnings', 'GET', $this, 'get_total_earnings_progess', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('stats/monthly-payments-expenses', 'GET', $this, 'get_monthly_payments_expenses', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('stats/recent-payments', 'GET', $this, 'get_recent_payments', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('stats/cashflow', 'GET', $this, 'get_cashflow', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('articles', 'GET', $this, 'get_articles', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('search', 'GET', $this, 'global_search', function () {
            return current_user_can('manage_options');
        });

        $this->routes->register_routes();
    }

    /**
     * @return [type]
     */
    public function get_stats_summary()
    {
        return rest_ensure_response([
            'unpaid'                 => $this->get_unpaid_invoices()->get_data(),
            'expenses'               => $this->get_expenses_current_month()->get_data()['total_expenses'] ?? 0,
            'current_month_earnings' => $this->get_current_month_earnings()->get_data(),
            'total_earnings'         => $this->get_total_earnings()->get_data(),
            'top_clients'            => $this->compute_top_clients(),
            'overdue'                => $this->compute_overdue_invoices(),
            'aging'                  => $this->compute_aging_receivables(),
            'month_comparison'       => $this->compute_month_comparison(),
        ]);
    }

    private function compute_month_comparison(): array
    {
        global $wpdb;
        $payments_table = ECWP_TABLE_PAYMENTS;

        $cur_start  = wp_date('Y-m-01');
        $cur_end    = wp_date('Y-m-t');
        $prev_start = wp_date('Y-m-01', strtotime('first day of last month'));
        $prev_end   = wp_date('Y-m-t',  strtotime('last day of last month'));
        $yoy_start  = wp_date('Y-m-01', strtotime('first day of this month last year'));
        $yoy_end    = wp_date('Y-m-t',  strtotime('last day of this month last year'));

        $fetch = function (string $start, string $end) use ($wpdb, $payments_table): float {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            return (float) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT SUM(amount) FROM {$payments_table} WHERE payment_date >= %s AND payment_date <= %s",
                    $start,
                    $end
                )
            );
        };

        $current   = $fetch($cur_start, $cur_end);
        $prev      = $fetch($prev_start, $prev_end);
        $yoy       = $fetch($yoy_start, $yoy_end);

        $mom_pct = $prev > 0 ? round(($current - $prev) / $prev * 100, 1) : null;
        $yoy_pct = $yoy  > 0 ? round(($current - $yoy)  / $yoy  * 100, 1) : null;

        return [
            'current'   => round($current, 2),
            'prev'      => round($prev, 2),
            'yoy'       => round($yoy, 2),
            'mom_pct'   => $mom_pct,
            'yoy_pct'   => $yoy_pct,
        ];
    }

    private function compute_top_clients(): array
    {
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT c.id, c.company_name, curr.symbol,
                    SUM(p.amount) AS total_paid
             FROM " . ECWP_TABLE_PAYMENTS . " AS p
             INNER JOIN " . ECWP_TABLE_CLIENTS  . " AS c    ON p.client_id   = c.id
             INNER JOIN " . ECWP_TABLE_CURRENCY . " AS curr ON c.currency_id = curr.id
             GROUP BY c.id, c.company_name, curr.symbol
             ORDER BY total_paid DESC
             LIMIT 5",
            ARRAY_A
        );

        return array_map(function ($r) {
            return [
                'id'           => (int) $r['id'],
                'company_name' => $r['company_name'],
                'symbol'       => $r['symbol'],
                'total_paid'   => round((float) $r['total_paid'], 2),
            ];
        }, $rows ?: []);
    }

    private function compute_overdue_invoices(): array
    {
        global $wpdb;
        $today   = current_time('Y-m-d');
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT inv.total_amount, inv.paid_amount, curr.symbol
                 FROM " . ECWP_TABLE_INVOICES  . " AS inv
                 INNER JOIN " . ECWP_TABLE_CLIENTS  . " AS c    ON inv.client_id  = c.id
                 INNER JOIN " . ECWP_TABLE_CURRENCY . " AS curr ON c.currency_id  = curr.id
                 WHERE inv.status_stats IN ('unpaid','partial')
                   AND inv.due_date < %s",
                $today
            ),
            ARRAY_A
        );

        $count  = 0;
        $totals = [];
        foreach ($rows as $row) {
            $total  = (float) $encrypt->decrypt($row['total_amount']);
            $paid   = (float) ($row['paid_amount'] ?? 0);
            $symbol = $row['symbol'];
            $count++;
            $totals[$symbol] = ($totals[$symbol] ?? 0) + max(0, $total - $paid);
        }

        $amounts = [];
        foreach ($totals as $symbol => $amount) {
            $amounts[] = ['symbol' => $symbol, 'amount' => round($amount, 2)];
        }

        return ['count' => $count, 'amounts' => $amounts];
    }

    private function compute_aging_receivables(): array
    {
        global $wpdb;
        $today   = current_time('Y-m-d');
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT inv.total_amount, inv.paid_amount, inv.due_date
                 FROM " . ECWP_TABLE_INVOICES . " AS inv
                 WHERE inv.status_stats IN ('unpaid','partial')
                   AND inv.due_date IS NOT NULL
                   AND inv.due_date < %s
                   AND (inv.is_template IS NULL OR inv.is_template = 0)",
                $today
            ),
            ARRAY_A
        );

        $buckets = [
            '0_30'  => ['count' => 0, 'amount' => 0.0],
            '31_60' => ['count' => 0, 'amount' => 0.0],
            '61_90' => ['count' => 0, 'amount' => 0.0],
            '90+'   => ['count' => 0, 'amount' => 0.0],
        ];

        foreach ($rows as $row) {
            $days_overdue = (int) floor((strtotime($today) - strtotime($row['due_date'])) / DAY_IN_SECONDS);
            $total        = (float) $encrypt->decrypt($row['total_amount']);
            $paid         = (float) ($row['paid_amount'] ?? 0);
            $outstanding  = max(0, $total - $paid);

            if ($days_overdue <= 30) {
                $key = '0_30';
            } elseif ($days_overdue <= 60) {
                $key = '31_60';
            } elseif ($days_overdue <= 90) {
                $key = '61_90';
            } else {
                $key = '90+';
            }

            $buckets[$key]['count']++;
            $buckets[$key]['amount'] += $outstanding;
        }

        // Round amounts
        foreach ($buckets as &$b) {
            $b['amount'] = round($b['amount'], 2);
        }

        return $buckets;
    }

    /**
     * @return [type]
     */
    public function get_data_dashboard()
    {
        return rest_ensure_response([
            'stats' => $this->get_stats_summary()->get_data(),
            'chart' => $this->get_monthly_payments_expenses()->get_data(),
            'recent_payments' => $this->get_recent_payments()->get_data()
        ]);
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

        // Inclure les factures 'unpaid' ET 'partial' (pour les partielles, le restant dû = total - paid_amount)
        $unpaid_invoices = $wpdb->get_results(
            "SELECT invoices.total_amount, invoices.paid_amount, invoices.status_stats, clients.currency_id, currency.symbol
            FROM {$invoices_table} AS invoices
            INNER JOIN {$clients_table} AS clients ON invoices.client_id = clients.id
            INNER JOIN {$currencies_table} AS currency ON clients.currency_id = currency.id
            WHERE invoices.status_stats IN ('unpaid', 'partial')",
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
            $symbol   = $invoice['symbol'];
            $total    = floatval($encrypt->decrypt($invoice['total_amount']));
            $paid     = floatval($invoice['paid_amount'] ?? 0);
            // Pour les partielles, seul le reste dû compte dans les impayés
            $amount = max(0, $total - $paid);

            if (!isset($unpaid_amounts_by_currency[$currency])) {
                $unpaid_amounts_by_currency[$currency] = array(
                    'total_amount' => 0,
                    'symbol'       => $symbol,
                );
            }
            $unpaid_amounts_by_currency[$currency]['total_amount'] += $amount;
        }
        foreach ($unpaid_amounts_by_currency as &$p) {
            $p['total_amount'] = number_format($p['total_amount'] ?? 0, 2, '.', ' ');
        }
        unset($p);

        return rest_ensure_response(array_values($unpaid_amounts_by_currency));
    }

    /**
     * @return [type]
     */
    public function get_expenses_current_month()
    {
        global $wpdb;
        $current_month_start = wp_date('Y-m-01');
        $current_month_end = wp_date('Y-m-t');
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

        $total_expenses = $current_month_expenses[0]['total_expenses'] ?? 0;

        // Obtenir le symbole de la devise par défaut séparément pour plus de robustesse
        $settings = new \ECWP\Admin\Settings\ECWP_Settings();
        $default_currency_id = $settings->get_setting('default_currency');
        $default_currency = $wpdb->get_var($wpdb->prepare(
            "SELECT symbol FROM " . ECWP_TABLE_CURRENCY . " WHERE id = %d",
            $default_currency_id ?: 2 // Euro par défaut
        )) ?: '€';

        $formatted_total_expenses = number_format((float) $total_expenses, 2, '.', ' ');
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
        $current_month_start = wp_date('Y-m-01');
        $current_month_end = wp_date('Y-m-t');

        $settings = new ECWP_Settings();
        $default_currency_id = $settings->get_setting('default_currency');

        $currencies_table = ECWP_TABLE_CURRENCY;
        $default_currency_symbol = $default_currency_id
            ? $wpdb->get_var($wpdb->prepare("SELECT symbol FROM {$currencies_table} WHERE id = %d", $default_currency_id))
            : null;
        $default_currency_symbol = $default_currency_symbol ?: '€';

        $payments_table = ECWP_TABLE_PAYMENTS;
        $current_month_earnings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT SUM(payments.amount) as total_earnings
        FROM {$payments_table} AS payments
        WHERE payments.payment_date >= %s
        AND payments.payment_date <= %s",
                $current_month_start,
                $current_month_end
            ),
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

        $settings = new ECWP_Settings();
        $default_currency_id = $settings->get_setting('default_currency');
        $currencies_table = ECWP_TABLE_CURRENCY;
        $default_currency_symbol = $default_currency_id
            ? $wpdb->get_var($wpdb->prepare("SELECT symbol FROM {$currencies_table} WHERE id = %d", $default_currency_id))
            : null;
        $default_currency_symbol = $default_currency_symbol ?: '€';

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
        $current_year = (int) current_time('Y');
        $payments_table = ECWP_TABLE_PAYMENTS;
        $expenses_table = ECWP_TABLE_EXPENSES;
        $payments = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT MONTH(payment_date) as month, SUM(amount) as total FROM {$payments_table}
                WHERE YEAR(payment_date)=%d GROUP BY MONTH(payment_date)",
                $current_year
            ),
            ARRAY_A
        );

        $expenses = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT MONTH(expense_date) as month, SUM(amount) as total FROM {$expenses_table}
                WHERE YEAR(expense_date)=%d GROUP BY MONTH(expense_date)",
                $current_year
            ),
            ARRAY_A
        );
        $months = [
            1 => __('January', 'my-easy-compta'),
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

        $monthly_data = [
            'months' => array_values($months),
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
        $payments_table = ECWP_TABLE_PAYMENTS;
        $invoices_table = ECWP_TABLE_INVOICES;
        $payments_methods_table = ECWP_TABLE_PAYMENTS_METHODS;
        $clients_table = ECWP_TABLE_CLIENTS;
        $currencies_table = ECWP_TABLE_CURRENCY;

        $recent_payments = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT invoices.invoice_number,
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
            LIMIT 10"
            ),
            ARRAY_A
        );

        if (!$recent_payments) {
            return rest_ensure_response([]);
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
        if ($method === 'name') {
            $results = $wpdb->get_results(
                $wpdb->prepare("SELECT name, ref, description, unit_price FROM {$articles_table} WHERE name LIKE %s LIMIT 10", '%' . $wpdb->esc_like($search) . '%')
            );
        } else {
            $results = $wpdb->get_results(
                $wpdb->prepare("SELECT name, ref, description, unit_price FROM {$articles_table} WHERE ref LIKE %s LIMIT 10", '%' . $wpdb->esc_like($search) . '%')
            );
        }

        do_action_ref_array('externe_article_results', [&$results, $search]);

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
                    <h3>🎉 <?php esc_html_e('Black Friday Sale - 30% OFF!', 'my-easy-compta'); ?> 🎉</h3>
                    <p><?php esc_html_e('Upgrade to myEasyCompta PREMIUM now and enjoy 30% off with the promo code', 'my-easy-compta'); ?>
                        <strong>BF</strong>. <?php esc_html_e('Offer valid until November 30!', 'my-easy-compta'); ?>
                    </p>

                    <a href="https://myeasycompta.com/produit/myeasycompta-premium/" target="_blank"
                        class="my-easy-compta-banner-button">
                        <?php esc_html_e('Claim Your Discount', 'my-easy-compta'); ?>
                    </a>
                </div>
                <div class="my-easy-compta-banner-never-show">
                    <a href="#">
                        <?php esc_html_e('Close & never show again', 'my-easy-compta'); ?>
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
                CAST(i.number AS CHAR) LIKE %s OR
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
                c.company_name LIKE %s OR
                q.id IN (SELECT quote_id FROM " . ECWP_TABLE_QUOTE_ELEMENTS . " WHERE item_name LIKE %s OR item_ref LIKE %s)
            )", '%' . $wpdb->esc_like($query) . '%', '%' . $wpdb->esc_like($query) . '%', '%' . $wpdb->esc_like($query) . '%', '%' . $wpdb->esc_like($query) . '%');
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
                    CAST(i.number AS CHAR) LIKE %s
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
                    cl.company_name LIKE %s
                )", $like_query, $like_query);
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

    /**
     * GET stats/cashflow?year=YYYY
     * Returns monthly earnings (payments received), expenses, and net balance for a given year.
     */
    public function get_cashflow(\WP_REST_Request $request)
    {
        global $wpdb;

        $year = isset($request['year']) ? intval($request['year']) : (int) current_time('Y');
        $payments_table = ECWP_TABLE_PAYMENTS;
        $expenses_table = ECWP_TABLE_EXPENSES;

        $payments = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT MONTH(payment_date) AS month, SUM(amount) AS total
                 FROM {$payments_table} WHERE YEAR(payment_date) = %d
                 GROUP BY MONTH(payment_date)",
                $year
            ),
            ARRAY_A
        );

        $expenses = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT MONTH(expense_date) AS month, SUM(amount) AS total
                 FROM {$expenses_table} WHERE YEAR(expense_date) = %d
                 GROUP BY MONTH(expense_date)",
                $year
            ),
            ARRAY_A
        );

        $month_names = [
            __('Jan', 'my-easy-compta'), __('Fév', 'my-easy-compta'), __('Mar', 'my-easy-compta'),
            __('Avr', 'my-easy-compta'), __('Mai', 'my-easy-compta'), __('Juin', 'my-easy-compta'),
            __('Juil', 'my-easy-compta'), __('Août', 'my-easy-compta'), __('Sep', 'my-easy-compta'),
            __('Oct', 'my-easy-compta'), __('Nov', 'my-easy-compta'), __('Déc', 'my-easy-compta'),
        ];

        $earnings_arr = array_fill(0, 12, 0.0);
        $expenses_arr = array_fill(0, 12, 0.0);

        foreach ($payments as $row) {
            $earnings_arr[(int) $row['month'] - 1] = round((float) $row['total'], 2);
        }
        foreach ($expenses as $row) {
            $expenses_arr[(int) $row['month'] - 1] = round((float) $row['total'], 2);
        }

        $net_arr = array_map(fn($e, $x) => round($e - $x, 2), $earnings_arr, $expenses_arr);

        // Determine available years (from 2020 to current+1)
        $first_year = (int) $wpdb->get_var("SELECT MIN(YEAR(payment_date)) FROM {$payments_table}");
        if (!$first_year || $first_year < 2020) $first_year = (int) current_time('Y');
        $current_year = (int) current_time('Y');
        $available_years = range($first_year, $current_year);

        return rest_ensure_response([
            'year'            => $year,
            'months'          => $month_names,
            'earnings'        => $earnings_arr,
            'expenses'        => $expenses_arr,
            'net'             => $net_arr,
            'available_years' => $available_years,
        ]);
    }

}
