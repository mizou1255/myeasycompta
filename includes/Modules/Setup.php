<?php
namespace ECWP\Admin;

class ECWP_Setup
{
    protected $routes;

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_submenu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_ecwp_handle_setup_step1', array($this, 'ecwp_handle_setup_step1'));
        add_action('wp_ajax_ecwp_handle_setup_step2', array($this, 'ecwp_handle_setup_step2'));
        add_action('wp_ajax_ecwp_handle_setup_step3', array($this, 'ecwp_handle_setup_step3'));
    }

    /**
     * Add submenu page for myEasyCompta Setup
     */
    public function add_submenu_page()
    {
        add_dashboard_page(
            '',
            '',
            'manage_options',
            'my-easy-compta-setup',
            array($this, 'render_page'),
        );
    }

    /**
     * Enqueue scripts and styles for myEasyCompta Setup page
     *
     * @param string $hook_suffix
     */
    public function enqueue_scripts($hook_suffix)
    {
        if ('dashboard_page_my-easy-compta-setup' === $hook_suffix) {
            wp_enqueue_script('my-easy-compta-setup', ECWP_URL . '/assets/js/my-easy-compta-setup.js', array('jquery'), ECWP_VERSION, true);
            wp_enqueue_script('notyf', ECWP_URL . '/assets/js/notyf.min.js', array('jquery'), ECWP_VERSION, true);
            wp_localize_script('my-easy-compta-setup', 'ecwp_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'ecwp_setup_nonce' => wp_create_nonce('ecwp_setup_nonce'),
                'step1_action' => 'ecwp_handle_setup_step1',
                'step2_action' => 'ecwp_handle_setup_step2',
                'step3_action' => 'ecwp_handle_setup_step3',
                'next_page_url' => admin_url('admin.php?page=my-easy-compta'),
            ));
            wp_enqueue_style('my-easy-compta-setup-css', ECWP_URL . '/assets/dist/setup.min.css', array(), ECWP_VERSION);
            wp_enqueue_style('my-easy-compta-notyf', ECWP_URL . '/assets/css/notyf.min.css', array(), ECWP_VERSION);
        }
    }

    /**
     * Check if myEasyCompta tables exist
     *
     * @return bool
     */
    public function tables_exist()
    {
        global $wpdb;

        $tables = [
            ECWP_TABLE_SETTINGS,
            ECWP_TABLE_CLIENTS,
            ECWP_TABLE_QUOTES,
            ECWP_TABLE_QUOTE_ELEMENTS,
            ECWP_TABLE_INVOICES,
            ECWP_TABLE_INVOICE_ELEMENTS,
            ECWP_TABLE_PAYMENTS,
            ECWP_TABLE_PAYMENTS_METHODS,
            ECWP_TABLE_EXPENSES,
            ECWP_TABLE_EXPENSES_ATTACHMENTS,
            ECWP_TABLE_EXPENSES_CATEGORIES,
            ECWP_TABLE_CURRENCY,
            ECWP_TABLE_VATS,
        ];

        foreach ($tables as $table) {
            $result = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %i", $table));

            if ($result !== $table) {
                return false;
            }
        }

        return true;
    }
    /**
     * Render myEasyCompta Setup page
     */
    public function render_page()
    {
        ?>
<div id="my-easy-compta-setup-wrapper">
    <div id="my-easy-compta-setup-content">
        <h1><?php esc_html_e('myEasyCompta - Setup Process', 'my-easy-compta');?></h1>

        <div id="setup-steps-container">
            <ul class="steps steps-vertical" id="my-easy-compta-setup-steps">
                <li class="step step1 active" data-step="1">
                    <div class="step-icon">
                        <i class="fas fa-database"></i>
                    </div>
                </li>
                <li class="step step2" data-step="2">
                    <div class="step-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                </li>
                <li class="step step3" data-step="3">
                    <div class="step-icon">
                        <i class="fas fa-table"></i>
                    </div>
                </li>
                <li class="step step4" data-step="4">
                    <div class="step-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </li>
            </ul>
            <!-- Étape 1 -->
            <div id="step1" class="setup-step active">
                <div class="step-number"><?php esc_html_e('Step', 'my-easy-compta');?> 1</div>
                <div class="step-title"><?php esc_html_e('Install database tables', 'my-easy-compta');?></div>
                <form id="my-easy-compta-setup-form" data-step="1" method="post" action="">
                    <input type="hidden" name="ecwp_setup_step" value="1">
                    <?php wp_nonce_field('ecwp_setup_nonce', 'security');?>
                    <button type="submit"
                        class="btn btn-primary"><?php esc_html_e('Let\'s GO', 'my-easy-compta');?><span
                            class="loading loading-spinner loading-sm"></span></button>
                </form>
            </div>

            <!-- Étape 2 -->
            <div id="step2" class="setup-step">
                <div class="step-number"><?php esc_html_e('Step', 'my-easy-compta');?> 2</div>
                <div class="step-title"><?php esc_html_e('Install settings datas', 'my-easy-compta');?></div>
                <form id="my-easy-compta-setup-form-step2" data-step="2" method="post" action="">
                    <input type="hidden" name="ecwp_setup_step" value="2">
                    <?php wp_nonce_field('ecwp_setup_nonce', 'security');?>
                    <div class="grid grid-cols-4 gap-4">
                        <div class="ecwp-group form-control">
                            <label class="ecwp-label label"
                                for="company-code"><?php esc_html_e('SIRET n°', 'my-easy-compta');?></label>
                            <input type="text" id="company-code" name="company_code"
                                class="ecwp-input input input-bordered" required="">
                        </div>
                        <div class="ecwp-group form-control">
                            <label class="ecwp-label label"
                                for="tax-number"><?php esc_html_e('Tax number', 'my-easy-compta');?></label>
                            <input type="text" id="tax-number" name="tax_number" class="ecwp-input input input-bordered"
                                required="">
                        </div>
                        <div class="ecwp-group form-control">
                            <label class="ecwp-label label"
                                for="company-name"><?php esc_html_e('Company name', 'my-easy-compta');?></label>
                            <input type="text" id="company-name" name="company_name"
                                class="ecwp-input input input-bordered" required="">
                        </div>
                        <div class="ecwp-group form-control">
                            <label class="ecwp-label label"
                                for="company-address"><?php esc_html_e('Address', 'my-easy-compta');?></label>
                            <input type="text" id="company-address" name="company_address"
                                class="ecwp-input input input-bordered" required="">
                        </div>
                        <div class="ecwp-group form-control">
                            <label class="ecwp-label label"
                                for="postal-code"><?php esc_html_e('Postal code', 'my-easy-compta');?></label>
                            <input type="text" id="postal-code" name="postal_code"
                                class="ecwp-input input input-bordered">
                        </div>
                        <div class="ecwp-group form-control">
                            <label class="ecwp-label label"
                                for="city"><?php esc_html_e('City', 'my-easy-compta');?></label>
                            <input type="text" id="city" name="city" class="ecwp-input input input-bordered">
                        </div>
                        <div class="ecwp-group form-control">
                            <label class="ecwp-label label"
                                for="country"><?php esc_html_e('Country', 'my-easy-compta');?></label>
                            <input type="text" id="country" name="country" class="ecwp-input input input-bordered">
                        </div>
                        <div class="ecwp-group form-control">
                            <label class="ecwp-label label"
                                for="company-email"><?php esc_html_e('Email', 'my-easy-compta');?></label>
                            <input type="email" id="company-email" name="company_email"
                                class="ecwp-input input input-bordered">
                        </div>
                        <div class="ecwp-group form-control">
                            <label class="ecwp-label label"
                                for="company-phone"><?php esc_html_e('Phone', 'my-easy-compta');?></label>
                            <input type="tel" id="company-phone" name="company_phone"
                                class="ecwp-input input input-bordered">
                        </div>
                        <div class="ecwp-group form-control">
                            <label class="ecwp-label label"
                                for="mobile-phone"><?php esc_html_e('Mobile', 'my-easy-compta');?></label>
                            <input type="tel" id="mobile-phone" name="mobile_phone"
                                class="ecwp-input input input-bordered">
                        </div>
                        <div class="ecwp-group form-control">
                            <label class="ecwp-label label"
                                for="fax"><?php esc_html_e('Fax', 'my-easy-compta');?></label>
                            <input type="tel" id="fax" name="fax" class="ecwp-input input input-bordered">
                        </div>
                    </div>
                    <hr class="mt-4 mb-4" />
                    <div class="ecwp-group form-group relative">
                        <label for="default_currency"
                            class="ecwp-label form-label"><?php esc_html_e('Default currency', 'my-easy-compta');?></label>
                        <select id="default_currency" name="default_currency"
                            class="ecwp-input input input-bordered w-full peer" required="">
                            <option>Sélectionner</option>
                            <option value="2">Euro - EUR (€) </option>
                            <option value="1">US Dollar - USD ($) </option>
                            <option value="3">British Pound - GBP (£) </option>
                            <option value="4">Japanese Yen - JPY (¥) </option>
                            <option value="5">Australian Dollar - AUD (A$) </option>
                            <option value="6">Canadian Dollar - CAD (C$) </option>
                            <option value="7">Swiss Franc - CHF (CHF) </option>
                            <option value="8">Chinese Yuan - CNY (¥) </option>
                            <option value="9">Swedish Krona - SEK (kr) </option>
                            <option value="10">New Zealand Dollar - NZD (NZ$) </option>
                        </select>
                    </div>
                    <div class="form-control mt-4 mb-1">
                        <label class="cursor-pointer">
                            <span
                                class="label-text mr-2 font-bold"><?php esc_html_e('Activate Vat', 'my-easy-compta');?></span>
                            <input type="checkbox" checked="" name="vat_active" class="wcpa-ui-toggle">
                        </label>
                    </div>
                    <div class="ecwp-group form-group relative" id="dafult_vat_div">
                        <label for="default_vat"
                            class="ecwp-label form-label"><?php esc_html_e('Default Vat', 'my-easy-compta');?></label>
                        <select id="default_vat" name="default_vat" class="ecwp-input input input-bordered w-full peer"
                            required="">
                            <option>Sélectionner</option>
                            <option value="1">20% </option>
                            <option value="2">15% </option>
                            <option value="3">10% </option>
                            <option value="4">5% </option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="ecwp-group form-control">
                            <label class="ecwp-label label"
                                for="quote-prefix"><?php esc_html_e('Quote prefix', 'my-easy-compta');?></label>
                            <input type="text" id="quote-prefix" name="quote_prefix"
                                class="ecwp-input input input-bordered" required="" value="EST">
                        </div>
                        <div class="ecwp-group form-control">
                            <label class="ecwp-label label" for="quote-first">Numéro 1er devis</label>
                            <input type="text" id="quote-first" name="quote_first"
                                class="ecwp-input input input-bordered" required="" value="1">
                        </div>
                        <div class="ecwp-group form-control">
                            <label class="ecwp-label label"
                                for="invoice-prefix"><?php esc_html_e('Invoice prefix', 'my-easy-compta');?></label>
                            <input type="text" id="invoice-prefix" name="invoice_prefix"
                                class="ecwp-input input input-bordered" required="" value="INV">
                        </div>
                        <div class="ecwp-group form-control">
                            <label class="ecwp-label label" for="invoice-first">Numéro 1ère facture</label>
                            <input type="text" id="invoice-first" name="invoice_first"
                                class="ecwp-input input input-bordered" required="" value="1">
                        </div>
                    </div>

                    <button type="submit"
                        class="btn btn-primary mt-4"><?php esc_html_e('Install configuration settings', 'my-easy-compta');?><span
                            class="loading loading-spinner loading-sm"></span></button>
                </form>
            </div>

            <!-- Étape 3 -->
            <div id="step3" class="setup-step">
                <div class="step-number"><?php esc_html_e('Step', 'my-easy-compta');?> 3</div>
                <div class="step-title"><?php esc_html_e('Import demo data', 'my-easy-compta');?></div>
                <form id="my-easy-compta-setup-form-step3" data-step="3" method="post" action="">
                    <input type="hidden" name="ecwp_setup_step" value="3">
                    <?php wp_nonce_field('ecwp_setup_nonce', 'security');?>
                    <button type="submit"
                        class="btn btn-primary"><?php esc_html_e('Import demo data', 'my-easy-compta');?><span
                            class="loading loading-spinner loading-sm"></span></button>
                    <button type="button" id="skip-step3"
                        class="btn btn-secondary"><?php esc_html_e('Skip', 'my-easy-compta');?></button>
                </form>
            </div>

            <div id="step4" id="my-easy-compta-setup-complete" style="display:none;">
                <h2><i class="fas fa-check-circle"></i></h2>
                <h2><?php esc_html_e('Setup complete!', 'my-easy-compta');?></h2>
                <p><?php esc_html_e('myEasyCompta is ready to use.', 'my-easy-compta');?></p>
                <p><a href="<?php echo esc_url(admin_url('admin.php?page=my-easy-compta')); ?>"
                        class="btn btn-primary"><?php esc_html_e('Go to the dashboard', 'my-easy-compta');?></a></p>
            </div>
        </div>
    </div>
</div>
<?php
}

    /**
     * Handle myEasyCompta setup Step 1 AJAX request
     */
    public function ecwp_handle_setup_step1()
    {
        check_ajax_referer('ecwp_setup_nonce', 'security');

        if ($this->tables_exist()) {
            wp_send_json_success(array('success' => true, 'message' => __('Tables already exist', 'my-easy-compta')));
        } else {
            $instance = new ECWP_Tables();
            $instance->create_tables();
            wp_send_json_success(array('success' => true, 'message' => __('Tables created successfully', 'my-easy-compta')));
        }
    }

    /**
     * Handle myEasyCompta setup Step 2 AJAX request
     */
    public function ecwp_handle_setup_step2()
    {
        check_ajax_referer('ecwp_setup_nonce', 'security');

        $params = [
            'company_code' => isset($_POST['company_code']) ? sanitize_text_field($_POST['company_code']) : '',
            'tax_number' => isset($_POST['tax_number']) ? sanitize_text_field($_POST['tax_number']) : '',
            'company_name' => isset($_POST['company_name']) ? sanitize_text_field($_POST['company_name']) : '',
            'company_address' => isset($_POST['company_address']) ? sanitize_text_field($_POST['company_address']) : '',
            'city' => isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '',
            'country' => isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '',
            'postal_code' => isset($_POST['postal_code']) ? sanitize_text_field($_POST['postal_code']) : '',
            'company_email' => isset($_POST['company_email']) ? sanitize_text_field($_POST['company_email']) : '',
            'company_phone' => isset($_POST['company_phone']) ? sanitize_text_field($_POST['company_phone']) : '',
            'mobile_phone' => isset($_POST['mobile_phone']) ? sanitize_text_field($_POST['mobile_phone']) : '',
            'fax' => isset($_POST['fax']) ? sanitize_text_field($_POST['fax']) : '',
            'default_currency' => isset($_POST['default_currency']) ? floatval($_POST['default_currency']) : 2,
            'vat_active' => isset($_POST['vat_active']) ? 1 : 0,
            'default_vat' => isset($_POST['default_vat']) ? floatval($_POST['default_vat']) : '',
            'quote_prefix' => isset($_POST['quote_prefix']) ? sanitize_text_field($_POST['quote_prefix']) : '',
            'invoice_prefix' => isset($_POST['invoice_prefix']) ? sanitize_text_field($_POST['invoice_prefix']) : '',
            'quote_first' => isset($_POST['quote_first']) ? floatval($_POST['quote_first']) : 1,
            'invoice_first' => isset($_POST['invoice_first']) ? floatval($_POST['invoice_first']) : 1,
        ];

        if ($this->data_settings_exist()) {
            wp_send_json_success(array('message' => __('Data settings already exist', 'my-easy-compta')));
        } else {
            $instance = new ECWP_Tables();
            $instance->import_data_settings($params);
            wp_send_json_success(array('message' => __('Data settings created successfully', 'my-easy-compta')));
        }
    }

    private function data_settings_exist()
    {
        global $wpdb;
        $result = $wpdb->get_var("SELECT COUNT(*) %i WHERE meta_key IS NOT NULL AND meta_value IS NOT NULL", ECWP_TABLE_SETTINGS);

        return $result > 0;
    }

    /**
     * Handle myEasyCompta setup Step 3 AJAX request
     */
    public function ecwp_handle_setup_step3()
    {
        check_ajax_referer('ecwp_setup_nonce', 'security');

        $instance = new ECWP_Tables();
        $instance->import_demo_data();
        wp_send_json_success(array('message' => __('Data settings created successfully', 'my-easy-compta')));

    }

}