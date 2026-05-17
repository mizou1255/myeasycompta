<?php
/**
 * Plugin Name: myEasyCompta
 * Description: Streamline your financial management with myEasyCompta, an all-in-one accounting plugin. Effortlessly handle quotes, invoices, expenses, and more, all within a sleek, user-friendly interface. Perfect for freelancers and small businesses looking to simplify their accounting processes.
 * Version: 2.1.1
 * Author: MELIOZ.dev
 * Author URI: https://myeasycompta.com
 * Text Domain: my-easy-compta
 * Domain Path: /languages/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.2
 * Tested up to: 6.9
 * Requires PHP: 8.0
 * Tags: accounting, quotes, invoices, expenses, Vue.js, TailwindCSS
 */

/**
 * myEasyCompta
 *
 * A comprehensive accounting plugin using Vue.js and TailwindCSS. Manage your quotes, invoices, expenses, and more with ease.
 *
 * @package myEasyCompta
 * @since 2.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

final class ECWP_Easy_Compta
{

    /**
     * Plugin version
     *
     * @var string
     */
    public $version = '2.1.1';
    private $version_migration_db = false;

    /**
     * Minimum PHP version required
     *
     * @var string
     */
    private $min_php = '8.0';

    /**
     * Holds various class instances
     *
     * @var array
     */
    private $container = [];

    /**
     * Singleton instance
     *
     * @var ECWP_Easy_Compta
     */
    private static $instance;

    /**
     * Initializes the ECWP_Easy_Compta class
     *
     * @return ECWP_Easy_Compta
     */
    public static function init()
    {
        if (!isset(self::$instance) && !(self::$instance instanceof ECWP_Easy_Compta)) {
            self::$instance = new ECWP_Easy_Compta();
            self::$instance->setup();
        }

        return self::$instance;
    }

    /**
     * Setup the plugin
     *
     * Sets up all the appropriate hooks and actions within the plugin.
     *
     * @return void
     */
    private function setup()
    {
        // Check for PHP version
        register_activation_hook(__FILE__, [$this, 'auto_deactivate']);

        if (!$this->is_supported_php()) {
            return;
        }

        // Define constants
        $this->define_constants();

        // Include required files
        $this->includes();

        // Instantiate classes
        $this->instantiate();

        // Initialize action hooks
        $this->init_actions();
    }

    /**
     * Define the plugin constants
     *
     * @return void
     */
    private function define_constants()
    {
        global $wpdb;

        if (!defined('ECWP_PREFIX')) {
            define('ECWP_PREFIX', $wpdb->prefix);
        }

        define('ECWP_VERSION', $this->version);
        define('ECWP_FILE', __FILE__);
        define('ECWP_PATH', dirname(ECWP_FILE));
        define('ECWP_PATH_DIR', plugin_dir_path(__FILE__));
        define('ECWP_INCLUDES', ECWP_PATH . '/includes');
        define('ECWP_URL', plugins_url('', ECWP_FILE));
        define('ECWP_ASSETS', ECWP_URL . '/assets');
        define('ECWP_UPLOADS', plugin_dir_path(__FILE__) . 'uploads');
        define('ECWP_UPLOADS_URL', ECWP_URL . '/uploads');
        // Legacy shared secret used only for migrating old AES-128-ECB licence keys.
        // Actual data encryption uses the per-installation key in wp_option 'ecwp_encryption_key'.
        define('ECWP_SECRET_KEY', 'c9a8b2d6eef97d2a98170fbc99b5218e');

        // Pour le dev local, définir ECWP_URL_LICENSE dans wp-config.php avant l'activation du plugin
        if ( ! defined( 'ECWP_URL_LICENSE' ) ) {
            define( 'ECWP_URL_LICENSE', 'https://myeasycompta.com' );
        }

        define('ECWP_TABLE_SETTINGS', ECWP_PREFIX . 'ecwp_settings');
        define('ECWP_TABLE_ARTICLES', ECWP_PREFIX . 'ecwp_articles');
        define('ECWP_TABLE_ARTICLES_CATEGORIES', ECWP_PREFIX . 'ecwp_articles_categories');
        define('ECWP_TABLE_CLIENTS', ECWP_PREFIX . 'ecwp_clients');
        define('ECWP_TABLE_CREDITS', ECWP_PREFIX . 'ecwp_credits');

        // Tables facturation électronique (OD-Ready)
        define('ECWP_TABLE_INVOICE_FISCAL_TRANSMISSIONS', ECWP_PREFIX . 'ecwp_invoice_fiscal_transmissions');
        define('ECWP_TABLE_INVOICE_FISCAL_LOGS', ECWP_PREFIX . 'ecwp_invoice_fiscal_logs');
        define('ECWP_TABLE_MOCK_PDP_TRANSMISSIONS', ECWP_PREFIX . 'ecwp_mock_pdp_transmissions');
        define('ECWP_TABLE_INVOICE_HISTORY', ECWP_PREFIX . 'ecwp_invoice_history');
        define('ECWP_TABLE_INVOICES', ECWP_PREFIX . 'ecwp_invoices');
        define('ECWP_TABLE_INVOICE_ELEMENTS', ECWP_PREFIX . 'ecwp_invoice_items');
        define('ECWP_TABLE_QUOTES', ECWP_PREFIX . 'ecwp_quotes');
        define('ECWP_TABLE_QUOTE_ELEMENTS', ECWP_PREFIX . 'ecwp_quote_elements');
        define('ECWP_TABLE_PAYMENTS', ECWP_PREFIX . 'ecwp_payments');
        define('ECWP_TABLE_PAYMENTS_METHODS', ECWP_PREFIX . 'ecwp_payment_methods');
        define('ECWP_TABLE_EXPENSES', ECWP_PREFIX . 'ecwp_expenses');
        define('ECWP_TABLE_EXPENSES_CATEGORIES', ECWP_PREFIX . 'ecwp_expenses_categories');
        define('ECWP_TABLE_EXPENSES_ATTACHMENTS', ECWP_PREFIX . 'ecwp_expenses_attachments');
        define('ECWP_TABLE_CURRENCY', ECWP_PREFIX . 'ecwp_currency');
        define('ECWP_TABLE_VATS', ECWP_PREFIX . 'ecwp_vat');

        define('ECWP_TABLE_DISBURSEMENTS', ECWP_PREFIX . 'ecwp_disbursements');
        define('ECWP_TABLE_ENTITIES', ECWP_PREFIX . 'ecwp_entities');
    }

    /**
     * Include the required files
     *
     * @return void
     */
    private function includes()
    {
        if (file_exists(ECWP_PATH . '/vendor/autoload.php')) {
            require_once ECWP_PATH . '/vendor/autoload.php';
        }

        require_once ECWP_INCLUDES . '/Migrations/Seed.php';
        require_once ECWP_INCLUDES . '/API/Routes.php';
        require_once ECWP_INCLUDES . '/Modules/App.php';
        require_once ECWP_INCLUDES . '/Modules/Clients.php';

        require_once ECWP_INCLUDES . '/Modules/Quotes.php';
        require_once ECWP_INCLUDES . '/Modules/Invoices.php';
        require_once ECWP_INCLUDES . '/Modules/Payments.php';
        require_once ECWP_INCLUDES . '/Modules/Credits.php';
        require_once ECWP_INCLUDES . '/Modules/Expenses.php';
        require_once ECWP_INCLUDES . '/Modules/Settings.php';
        require_once ECWP_INCLUDES . '/Modules/Setup.php';
        require_once ECWP_INCLUDES . '/Modules/PDFGenerator.php';
        require_once ECWP_INCLUDES . '/Modules/Encrypt.php';
        require_once ECWP_INCLUDES . '/Modules/Addons.php';
        require_once ECWP_INCLUDES . '/Modules/InvoiceHistory.php';
        require_once ECWP_INCLUDES . '/Modules/InvoiceReminders.php';
        require_once ECWP_INCLUDES . '/Modules/Analytics.php';
        require_once ECWP_INCLUDES . '/Modules/EmailTemplate.php';
        require_once ECWP_INCLUDES . '/Modules/Notifications.php';

        // Facturation électronique (core)
        require_once ECWP_INCLUDES . '/einvoicing/model/InvoiceModel.php';
        require_once ECWP_INCLUDES . '/einvoicing/facturx/FacturXGenerator.php';
        require_once ECWP_INCLUDES . '/einvoicing/workflow/PDPInterface.php';
        require_once ECWP_INCLUDES . '/einvoicing/workflow/ProviderManager.php';

        // Providers PDP built-in
        require_once ECWP_INCLUDES . '/einvoicing/providers/ChorusProProvider.php';
        require_once ECWP_INCLUDES . '/einvoicing/providers/PennylaneProvider.php';
        require_once ECWP_INCLUDES . '/einvoicing/providers/JeFactureProvider.php';
        require_once ECWP_INCLUDES . '/einvoicing/providers/GenericProvider.php';

    }

    /**
     * Instantiate classes
     *
     * @return void
     */
    private function instantiate()
    {
        $this->container['tables'] = new ECWP\Admin\ECWP_Tables();
        $this->container['admin'] = new ECWP\Admin\ECWP_APP();
        $this->container['clients'] = new ECWP\Admin\ECWP_Clients();
        $this->container['quotes'] = new ECWP\Admin\ECWP_Quotes();
        $this->container['invoices'] = new ECWP\Admin\ECWP_Invoices();
        $this->container['credits'] = new ECWP\Admin\ECWP_Credits();
        $this->container['payments'] = new ECWP\Admin\ECWP_Payments();
        $this->container['expenses'] = new ECWP\Admin\ECWP_Expenses();
        $this->container['settings'] = new ECWP\Admin\Settings\ECWP_Settings();
        $this->container['setup'] = new ECWP\Admin\ECWP_Setup();
        $this->container['addons']    = new ECWP\Admin\ECWP_Addons();
        $this->container['reminders'] = new ECWP\Admin\InvoiceReminders\ECWP_InvoiceReminders();
        $this->container['analytics'] = new ECWP\Admin\Analytics\ECWP_Analytics();
        $this->container['notifications'] = new ECWP\Admin\ECWP_Notifications();

        // Facturation électronique : initialisation propre à venir
    }

    /**
     * Initialize WordPress action hooks
     *
     * @return void
     */
    private function init_actions()
    {
        // Localize the plugin
        add_action('init', [$this, 'localization_setup']);

        // Create tables on activation
        register_activation_hook(ECWP_FILE, [$this, 'install_configuration']);
        register_activation_hook(ECWP_FILE, [$this, 'ecwp_flush_rewrite_rules']);
        register_activation_hook(ECWP_FILE, [$this, 'ecwp_encrypt_key']);

        // Deactivation — flush rules only, data is preserved
        // Tables and encryption key are only removed on full uninstall (uninstall.php)
        register_deactivation_hook(ECWP_FILE, [$this, 'ecwp_deactivate']);

        // ── Unpaid invoice reminders cron ─────────────────────────────────────
        register_activation_hook(ECWP_FILE, function () {
            if (!wp_next_scheduled('ecwp_invoice_reminders_daily')) {
                wp_schedule_event(time(), 'daily', 'ecwp_invoice_reminders_daily');
            }
        });
        register_deactivation_hook(ECWP_FILE, function () {
            $ts = wp_next_scheduled('ecwp_invoice_reminders_daily');
            if ($ts) wp_unschedule_event($ts, 'ecwp_invoice_reminders_daily');
        });
        add_action('ecwp_invoice_reminders_daily', ['ECWP\Admin\InvoiceReminders\ECWP_InvoiceReminders', 'send_reminders']);

        add_action('init', [$this, 'ecwp_add_rewrite_rules']);
        add_filter('query_vars', [$this, 'ecwp_query_vars']);
        add_filter('admin_init', [$this, 'ecwp_redirect_after_activation']);
        // Toujours forcer le wizard tant que l'installation (tables + réglages) n'est pas terminée
        add_action('admin_init', [$this, 'ecwp_redirect_to_setup_if_needed'], 1);

        add_action('admin_init', [$this, 'maybe_run_migration']);
        add_action('admin_notices', [$this, 'migration_admin_notice']);
        add_action('admin_notices', [$this, 'custom_permalink_structure_notice']);

    }

    /**
     * Setup plugin localization
     *
     * @return void
     */
    public function localization_setup()
    {
        load_plugin_textdomain('my-easy-compta', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    /**
     * Check if the PHP version is supported
     *
     * @return bool
     */
    public function is_supported_php()
    {
        return version_compare(PHP_VERSION, $this->min_php, '>=');
    }

    /**
     * Deactivate the plugin if PHP version is not supported
     *
     * @return void
     */
    public function auto_deactivate()
    {
        if (!$this->is_supported_php()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                sprintf(
                    /* translators: %s is the minimum PHP version required */
                    esc_html__(
                        'The <strong>myEasyCompta</strong> plugin requires PHP version %s or higher.',
                        'my-easy-compta'
                    ),
                    esc_html($this->min_php)
                ),
                esc_html__('Plugin activation error', 'my-easy-compta'),
                ['response' => 200, 'back_link' => true]
            );
        }
    }

    public function install_configuration()
    {
        // Les tables sont créées par le wizard (Step 1) — pas ici.
        // Laisser un délai confortable : le setup est une page cachée, on veut pouvoir y revenir.
        set_transient('ecwp_activation_redirect', true, DAY_IN_SECONDS);
    }

    /**
     * Redirige vers le wizard si les tables / réglages ne sont pas prêts.
     * Évite l'état "plugin sans tables".
     */
    public function ecwp_redirect_to_setup_if_needed()
    {
        if (!is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        // Ne pas casser l'accès à la page permaliens
        global $pagenow;
        if ($pagenow === 'options-permalink.php') {
            return;
        }

        $page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';
        if ($page === 'my-easy-compta-setup') {
            return;
        }

        // 1. Option flag — chemin rapide (set at end of setup step 2)
        if (get_option('ecwp_setup_complete') === '1') {
            return;
        }

        // 2. Auto-migration : installation existante sans le flag.
        // On vérifie company_name (inséré UNIQUEMENT par le Step 2 du wizard) et non pas
        // n'importe quelle ligne de settings (migration_1_1_0 en insère 11 dès le Step 1).
        global $wpdb;
        $settings_table = defined('ECWP_TABLE_SETTINGS') ? ECWP_TABLE_SETTINGS : $wpdb->prefix . 'ecwp_settings';
        $table_exists   = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $settings_table)) === $settings_table;
        $step2_done     = $table_exists
            ? (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$settings_table} WHERE meta_key = %s AND meta_value != ''",
                'company_name'
            )) > 0
            : false;

        if ($table_exists && $step2_done) {
            // company_name présent → setup step 2 complété sur une install antérieure
            update_option('ecwp_setup_complete', '1');
            return;
        }

        // 3. Setup incomplet → rediriger vers le wizard
        wp_safe_redirect(admin_url('index.php?page=my-easy-compta-setup'));
        exit;
    }

    public function maybe_run_migration()
    {
        // Ne jamais migrer si les tables de base n'existent pas encore (install propre avant wizard).
        global $wpdb;
        $settings_table = defined('ECWP_TABLE_SETTINGS') ? ECWP_TABLE_SETTINGS : $wpdb->prefix . 'ecwp_settings';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $settings_table)) !== $settings_table) {
            return;
        }

        if (isset($_POST['run_migration_now']) && check_admin_referer('run_migration_action', 'run_migration_nonce')) {
            $this->run_migrations();
            add_action('admin_notices', function () {
                echo '<div class="ecwp-notice notice notice-success is-dismissible">
                        <p>' . esc_html__('Database migration completed successfully.', 'my-easy-compta') . '</p>
                      </div>';
            });
            return;
        }

        // Auto-run pending migrations silently on admin_init
        $installed_db_version = get_option('ecwp_db_version', '1.0.0');
        $latest_migration     = '2.6.0';
        if (version_compare($installed_db_version, $latest_migration, '<')) {
            $this->run_migrations();
        }
    }

    public function ecwp_add_rewrite_rules()
    {
        add_rewrite_rule(
            '^my-easy-compta/uploads/(.*)$',
            'index.php?ecwp_file=$matches[1]',
            'top'
        );
    }

    public function ecwp_query_vars($vars)
    {
        $vars[] = 'ecwp_file';
        return $vars;
    }

    public function ecwp_flush_rewrite_rules()
    {
        $this->ecwp_add_rewrite_rules();
        flush_rewrite_rules();
    }

    public function ecwp_encrypt_key()
    {
        if (false === get_option('ecwp_encryption_key')) {
            $encryption_key = bin2hex(random_bytes(32));
            add_option('ecwp_encryption_key', $encryption_key);
        }
    }

    public function ecwp_delete_encrypt_key()
    {
        delete_option('ecwp_encryption_key');
    }

    private function run_migrations()
    {
        $migrations = [
            '1.1.0' => ECWP_INCLUDES . '/Migrations/migration_1_1_0.php',
            '1.2.3' => ECWP_INCLUDES . '/Migrations/migration_1_2_3.php',
            '1.4.0' => ECWP_INCLUDES . '/Migrations/migration_1_4_0.php',
            '1.5.0' => ECWP_INCLUDES . '/Migrations/migration_1_5_0_e_invoicing.php',
            '1.5.1' => ECWP_INCLUDES . '/Migrations/migration_1_5_1_payments_methods.php',
            '2.0.0' => ECWP_INCLUDES . '/Migrations/migration_2_0_0_partial_payments.php',
            '2.1.0' => ECWP_INCLUDES . '/Migrations/migration_2_1_0_entities.php',
            '2.3.0' => ECWP_INCLUDES . '/Migrations/migration_2_3_0_internal_notes.php',
            '2.4.0' => ECWP_INCLUDES . '/Migrations/migration_2_4_0_archive_clients.php',
            '2.5.0' => ECWP_INCLUDES . '/Migrations/migration_2_5_0_templates.php',
            '2.5.1' => ECWP_INCLUDES . '/Migrations/migration_2_5_1_repair.php',
            '2.6.0' => ECWP_INCLUDES . '/Migrations/migration_2_6_0_optional_items.php',
        ];

        $installed_db_version = get_option('ecwp_db_version', '1.0.0');
        $last_migration_version = $installed_db_version;

        foreach ($migrations as $version => $file) {
            if (version_compare($installed_db_version, $version, '<')) {
                require_once $file;
                $migration_function = 'run_migration_' . str_replace('.', '_', $version);
                if (function_exists($migration_function)) {
                    $migration_function();
                    $last_migration_version = $version; // Mettre à jour après chaque migration réussie
                }
            }
        }

        // Mettre à jour la version DB avec la dernière migration exécutée
        // Important : utiliser la version de migration, pas ECWP_VERSION
        // Car le plugin peut être en 1.4.6 alors que la DB est en 1.5.0
        if (version_compare($last_migration_version, $installed_db_version, '>')) {
            update_option('ecwp_db_version', $last_migration_version);
        }
    }

    public function migration_admin_notice()
    {
        $installed_db_version = get_option('ecwp_db_version', '1.0.0');

        // Show migration notice for any pending migration up to current plugin version
        if (version_compare($installed_db_version, ECWP_VERSION, '<')) {
            $description = esc_html__('Une mise à jour de la base de données est disponible. Cliquez pour mettre à jour.', 'my-easy-compta');
            echo '<div class="ecwp-notice notice notice-warning">
                <p><strong>' . esc_html__('myEasyCompta - Mise à jour base de données requise', 'my-easy-compta') . '</strong></p>
                <p>' . $description . '</p>
                <form method="post">
                    ' . wp_nonce_field('run_migration_action', 'run_migration_nonce') . '
                    <p><input type="submit" name="run_migration_now" class="button button-primary" value="' . esc_attr__('Mettre à jour la base de données', 'my-easy-compta') . '" /></p>
                </form>
            </div>';
        }
    }
    public function custom_permalink_structure_notice()
    {
        $current_permalink_structure = get_option('permalink_structure');

        // N'afficher que sur les pages myEasyCompta (sinon c'est trop intrusif).
        $page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';
        $is_ecwp_page = (strpos($page, 'my-easy-compta') === 0);
        if (!$is_ecwp_page) {
            return;
        }

        // Exiger seulement des permaliens "pretty" (structure non vide), pas un format spécifique.
        if (empty($current_permalink_structure)) {
            $permalink_page_url = admin_url('options-permalink.php');
            echo '<div class="notice notice-warning"><p><strong>' . esc_html__("myEasyCompta", "my-easy-compta") . ':</strong> ' .
                esc_html__("Pour fonctionner correctement, l\'extension a besoin des permaliens activés (structure non vide).", "my-easy-compta") .
                '</p><p><a href="' . esc_url($permalink_page_url) . '" class="button button-primary">' . esc_html__("Modify permalinks", "my-easy-compta") . '</a></p></div>';
        }
    }

    public function ecwp_deactivate()
    {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $active_plugins = (array) get_option('active_plugins', []);
        foreach ($active_plugins as $plugin_file) {
            $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
            if (!is_file($plugin_path)) {
                continue;
            }

            $data = get_plugin_data($plugin_path, false, false);
            $requires = $data['RequiresPlugins'] ?? '';
            if ($requires && stripos($requires, 'my-easy-compta') !== false) {
                wp_die(
                    esc_html__('Vous ne pouvez pas désactiver myEasyCompta tant que des addons dépendants sont actifs.', 'my-easy-compta'),
                    esc_html__('Erreur de désactivation', 'my-easy-compta'),
                    ['response' => 200, 'back_link' => true]
                );
            }
        }

        flush_rewrite_rules();
    }

    public function ecwp_redirect_after_activation()
    {
        if (get_transient('ecwp_activation_redirect')) {
            delete_transient('ecwp_activation_redirect');

            if (is_network_admin()) {
                return;
            }

            // La page setup est une "dashboard page" => URL index.php
            wp_redirect(admin_url('index.php?page=my-easy-compta-setup'));
            exit;
        }
    }
}

/**
 * Initialize the ECWP_Easy_Compta plugin
 *
 * @return ECWP_Easy_Compta
 */

ECWP_Easy_Compta::init();
