<?php
/**
 * Plugin Name: myEasyCompta
 * Description: Streamline your financial management with myEasyCompta, an all-in-one accounting plugin. Effortlessly handle quotes, invoices, expenses, and more, all within a sleek, user-friendly interface. Perfect for freelancers and small businesses looking to simplify their accounting processes.
 * Version: 1.4.6
 * Author: MELIOZ.dev
 * Author URI: https://myeasycompta.com
 * Text Domain: my-easy-compta
 * Domain Path: /languages/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.2
 * Tested up to: 6.7
 * Requires PHP: 8.0
 * Tags: accounting, quotes, invoices, expenses, Vue.js, TailwindCSS
 */

/**
 * myEasyCompta
 *
 * A comprehensive accounting plugin using Vue.js and TailwindCSS. Manage your quotes, invoices, expenses, and more with ease.
 *
 * @package myEasyCompta
 * @since 1.4.6
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
    public $version = '1.4.6';
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
        define('ECWP_SECRET_KEY', 'c9a8b2d6eef97d2a98170fbc99b5218e');
        
        // Détection automatique de l'environnement local pour l'URL de licence
        $current_domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '');
        $is_local = (
            strpos($current_domain, '.local') !== false ||
            strpos($current_domain, 'localhost') !== false ||
            strpos($current_domain, '127.0.0.1') !== false
        );
        
        if ($is_local) {
            define('ECWP_URL_LICENSE', 'https://myeasycompta-site.local');
        } else {
            define('ECWP_URL_LICENSE', 'https://myeasycompta.com');
        }

        define('ECWP_TABLE_SETTINGS', ECWP_PREFIX . 'ecwp_settings');
        define('ECWP_TABLE_ARTICLES', ECWP_PREFIX . 'ecwp_articles');
        define('ECWP_TABLE_ARTICLES_CATEGORIES', ECWP_PREFIX . 'ecwp_articles_categories');
        define('ECWP_TABLE_CLIENTS', ECWP_PREFIX . 'ecwp_clients');
        define('ECWP_TABLE_CREDITS', ECWP_PREFIX . 'ecwp_credits');
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
    }

    /**
     * Include the required files
     *
     * @return void
     */
    private function includes()
    {
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
        $this->container['addons'] = new ECWP\Admin\ECWP_Addons();
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

        // Drop tables on deactivation
        register_deactivation_hook(ECWP_FILE, [$this, 'ecwp_deactivate']);
        register_deactivation_hook(ECWP_FILE, [$this, 'ecwp_delete_encrypt_key']);
        register_deactivation_hook(ECWP_FILE, [$this->container['tables'], 'drop_tables']);

        add_action('init', [$this, 'ecwp_add_rewrite_rules']);
        add_filter('query_vars', [$this, 'ecwp_query_vars']);
        add_filter('admin_init', [$this, 'ecwp_redirect_after_activation']);

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
        set_transient('ecwp_activation_redirect', true, 30);
    }

    public function maybe_run_migration()
    {
        if (isset($_POST['run_migration_now']) && check_admin_referer('run_migration_action', 'run_migration_nonce')) {
            $this->run_migrations();
            add_action('admin_notices', function () {
                echo '<div class="ecwp-notice notice notice-success is-dismissible">
                        <p>' . __('Database migration completed successfully.', 'my-easy-compta') . '</p>
                      </div>';
            });
        }
    }

    public function ecwp_add_rewrite_rules()
    {
        add_rewrite_rule('^my-easy-compta/uploads/(.*)$',
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
        ];

        $installed_db_version = get_option('ecwp_db_version', '1.0.0');
        foreach ($migrations as $version => $file) {
            if (version_compare($installed_db_version, $version, '<')) {
                require_once $file;
                $migration_function = 'run_migration_' . str_replace('.', '_', $version);
                if (function_exists($migration_function)) {
                    $migration_function();
                }
            }
        }
        update_option('ecwp_db_version', ECWP_VERSION);
    }

    public function migration_admin_notice()
    {
        $installed_db_version = get_option('ecwp_db_version', '1.0.0');
        if ($this->version_migration_db == true && version_compare('installed_db_version', '1.4.0', '<')) {
            if (version_compare($installed_db_version, $this->version, '<')) {
                echo '<div class="ecwp-notice notice notice-warning">
                    <p>' . __('myEasyCompta requires a database update.', 'my-easy-compta') . '</p>
                    <form method="post">
                        ' . wp_nonce_field('run_migration_action', 'run_migration_nonce') . '
                        <p><input type="submit" name="run_migration_now" class="button button-primary" value="' . esc_attr__('Update Database', 'my-easy-compta') . '" /></p>
                    </form>
                </div><div class="bg-blur"></div>';
            }
        }
    }
    public function custom_permalink_structure_notice()
    {
        $current_permalink_structure = get_option('permalink_structure');

        if ($current_permalink_structure !== '/%postname%/') {
            $permalink_page_url = admin_url('options-permalink.php');

            echo '<div class="notice-wrapper">';
            echo '  <div class="ecwp-notice notice notice-error">';
            echo '      <p>' . __('The <b>myEasyCompta</b> plugin requires the permalink structure to be set to "/%postname%/". Please modify it for proper functionality by clicking the button below.', 'my-easy-compta') . '</p>';
            echo '      <p><a href="' . esc_url($permalink_page_url) . '" class="button button-primary">' . __('Modify permalinks', 'my-easy-compta') . '</a></p>';
            echo '  </div>';
            echo '  <div class="bg-blur"></div>';
            echo '</div>';
        }
    }

    public function ecwp_deactivate()
    {
        $addons = [
            'my-easy-compta-signature/my-easy-compta-signature.php',
            'my-easy-compta-user/my-easy-compta-user.php',
            'my-easy-compta-backup/my-easy-compta-backup.php',
        ];

        foreach ($addons as $addon) {
            if (is_plugin_active($addon)) {
                wp_die(
                    esc_html__('You cannot deactivate the myEasyCompta plugin while add-ons are enabled.', 'my-easy-compta'),
                    esc_html__('Plugin deactivation error', 'my-easy-compta'),
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

            wp_redirect(admin_url('admin.php?page=my-easy-compta-setup'));
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
