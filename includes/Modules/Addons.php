<?php

namespace ECWP\Admin;

class ECWP_Addons
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_submenu_page'));
    }

    /**
     * @return [type]
     */
    public function add_submenu_page()
    {
        add_submenu_page(
            'my-easy-compta',
            __('Addons', 'my-easy-compta'),
            __('Addons', 'my-easy-compta'),
            'manage_options',
            'my-easy-compta-addons',
            array($this, 'render_page'),
            20
        );
        add_filter('parent_file', array($this, 'add_pro_badge_to_menu'));
    }

    public function add_pro_badge_to_menu($parent_file)
    {
        global $submenu;

        $menu_slug = 'my-easy-compta';
        $submenu_slug = 'my-easy-compta-addons';

        if (isset($submenu[$menu_slug])) {
            foreach ($submenu[$menu_slug] as $key => $menu_item) {
                if ($menu_item[2] === $submenu_slug) {
                    $submenu[$menu_slug][$key][0] .= ' <span class="ecwp-pro-badge">Premium</span>';
                    break;
                }
            }
        }

        return $parent_file;
    }
    /**
     * @return [type]
     */
    public function render_page()
    {
        ?>
<div class="wrap">
    <h1><?php esc_html_e('myEasyCompta Addons', 'my-easy-compta');?></h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">

        <div class="card p-6 bg-base-100 shadow-xl flex flex-col">
            <div class="card-body flex flex-col flex-grow">
                <h2 class="card-title mb-2"><?php esc_html_e('myEasyCompta Email', 'my-easy-compta');?></h2>
                <p class="mb-2 flex-grow">
                    <?php esc_html_e('Enhance myEasyCompta with email notification functionalities. Customize email templates, send estimates or invoices directly, and log sent emails. Ideal for maintaining smooth email communication with clients.', 'my-easy-compta');?>
                </p>
                <div class="flex card-actions justify-end mt-auto btn-rounded">
                    <a class="btn btn-primary" href="https://myeasycompta.com/addons-all/myeasycompta-e-mail/"
                        target="_blank"><?php esc_html_e('Voir plus', 'my-easy-compta');?></a>
                </div>
            </div>
        </div>
        <div class="card p-6 bg-base-100 shadow-xl flex flex-col">
            <div class="card-body flex flex-col flex-grow">
                <h2 class="card-title mb-2"><?php esc_html_e('myEasyCompta Export', 'my-easy-compta');?></h2>
                <p class="mb-2 flex-grow">
                    <?php esc_html_e('Export your financial data in various formats such as CSV and Excel. Customize export fields to include customers, quotes, invoices, payments, expenses, and statistics.', 'my-easy-compta');?>
                </p>
                <div class="flex card-actions justify-end mt-auto btn-rounded">
                    <a class="btn btn-primary" href="https://myeasycompta.com/addons-all/myeasycompta-export/"
                        target="_blank"><?php esc_html_e('Voir plus', 'my-easy-compta');?></a>
                </div>
            </div>
        </div>
        <div class="card p-6 bg-base-100 shadow-xl flex flex-col">
            <div class="card-body flex flex-col flex-grow">
                <h2 class="card-title mb-2"><?php esc_html_e('myEasyCompta Planning', 'my-easy-compta');?></h2>
                <p class="mb-2 flex-grow">
                    <?php esc_html_e('Integrate a planning module to manage your schedule. Create and assign tasks with specific categories, and track project deadlines and workloads efficiently.', 'my-easy-compta');?>
                </p>
                <div class="flex card-actions justify-end mt-auto btn-rounded">
                    <a class="btn btn-primary" href="https://myeasycompta.com/addons-all/myeasycompta-planning/"
                        target="_blank"><?php esc_html_e('Voir plus', 'my-easy-compta');?></a>
                </div>
            </div>
        </div>
        <div class="card p-6 bg-base-100 shadow-xl flex flex-col">
            <div class="card-body flex flex-col flex-grow">
                <h2 class="card-title mb-2"><?php esc_html_e('myEasyCompta Stats', 'my-easy-compta');?></h2>
                <p class="mb-2 flex-grow">
                    <?php esc_html_e('Get detailed statistical analysis of your financial data. Generate various reports on income, expenses, and profitability with graphical representations for easier interpretation.', 'my-easy-compta');?>
                </p>
                <div class="flex card-actions justify-end mt-auto btn-rounded">
                    <a class="btn btn-primary" href="https://myeasycompta.com/addons-all/myeasycompta-stats/"
                        target="_blank"><?php esc_html_e('Voir plus', 'my-easy-compta');?></a>
                </div>
            </div>
        </div>
        <div class="card p-6 bg-base-100 shadow-xl flex flex-col">
            <div class="card-body flex flex-col flex-grow">
                <h2 class="card-title mb-2"><?php esc_html_e('myEasyCompta User', 'my-easy-compta');?></h2>
                <p class="mb-2 flex-grow">
                    <?php esc_html_e('Create secure access for your customers. Allow them to view quotes, invoices, payments, and statistics, and update their information and passwords through a dedicated dashboard.', 'my-easy-compta');?>
                </p>
                <div class="flex card-actions justify-end mt-auto btn-rounded">
                    <a class="btn btn-primary" href="https://myeasycompta.com/addons-all/myeasycompta-compte-client/"
                        target="_blank"><?php esc_html_e('Voir plus', 'my-easy-compta');?></a>
                </div>
            </div>
        </div>
        <div class="card p-6 bg-base-100 shadow-xl flex flex-col">
            <div class="card-body flex flex-col flex-grow">
                <h2 class="card-title mb-2"><?php esc_html_e('myEasyCompta SIRET Search', 'my-easy-compta');?></h2>
                <p class="mb-2 flex-grow">
                    <?php esc_html_e('The Easy-Compta SIRET plugin adds a field to quickly retrieve customer information using the French government business directory API (https://api.gouv.fr). This enhances the efficiency of managing client data within the Easy-Compta system.', 'my-easy-compta');?>
                </p>
                <div class="flex card-actions justify-end mt-auto btn-rounded">
                    <a class="btn btn-primary" href="https://myeasycompta.com/addons-all/myeasycompta-siret/"
                        target="_blank"><?php esc_html_e('Voir plus', 'my-easy-compta');?></a>
                </div>
            </div>
        </div>
        <div class="card p-6 bg-base-100 shadow-xl flex flex-col">
            <div class="card-body flex flex-col flex-grow">
                <h2 class="card-title mb-2"><?php esc_html_e('myEasyCompta Woo', 'my-easy-compta');?></h2>
                <p class="mb-2 flex-grow">
                    <?php esc_html_e('Integrate myEasyCompta with WooCommerce to automatically generate invoices for each new order. Sync WooCommerce sales data, manage transactions, track sales, and handle customer invoicing seamlessly.', 'my-easy-compta');?>
                </p>
                <div class="flex card-actions justify-end mt-auto btn-rounded">
                    <a class="btn btn-primary" href="https://myeasycompta.com/addons-all/myeasycompta-woo/"
                        target="_blank"><?php esc_html_e('Voir plus', 'my-easy-compta');?></a>
                </div>
            </div>
        </div>
        <div class="card p-6 bg-base-100 shadow-xl flex flex-col">
            <div class="card-body flex flex-col flex-grow">
                <h2 class="card-title mb-2"><?php esc_html_e('myEasyCompta Backup', 'my-easy-compta');?></h2>
                <p class="mb-2 flex-grow">
                    <?php esc_html_e('myEasyCompta Backup plugin provides integrated solutions for securely backing up, restoring, and managing financial data.', 'my-easy-compta');?>
                </p>
                <div class="flex card-actions justify-end mt-auto btn-rounded">
                    <a class="btn btn-primary" href="https://myeasycompta.com/addons-all/myeasycompta-backup/"
                        target="_blank"><?php esc_html_e('Voir plus', 'my-easy-compta');?></a>
                </div>
            </div>
        </div>
        <div class="card p-6 bg-base-100 shadow-xl flex flex-col">
            <div class="card-body flex flex-col flex-grow">
                <h2 class="card-title mb-2"><?php esc_html_e('myEasyCompta Signature', 'my-easy-compta');?></h2>
                <p class="mb-2 flex-grow">
                    <?php esc_html_e('myEasyCompta Signature allows myEasyCompta users to easily collect digital signatures on quotes, ensuring secure and efficient document approval.', 'my-easy-compta');?>
                </p>
                <div class="flex card-actions justify-end mt-auto btn-rounded">
                    <a class="btn btn-primary" href="https://myeasycompta.com/addons-all/myeasycompta-signature/"
                        target="_blank"><?php esc_html_e('Voir plus', 'my-easy-compta');?></a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
}

}