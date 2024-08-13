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
        <?php $this->render_addon_card('my-easy-compta-email', __('myEasyCompta Email', 'my-easy-compta'), __('Enhance myEasyCompta with email notification functionalities. Customize email templates, send estimates or invoices directly, and log sent emails. Ideal for maintaining smooth email communication with clients.', 'my-easy-compta'), 'https://myeasycompta.com/addons-all/myeasycompta-e-mail/');?>
        <?php $this->render_addon_card('my-easy-compta-export', __('myEasyCompta Export', 'my-easy-compta'), __('Export your financial data in various formats such as CSV and Excel. Customize export fields to include customers, quotes, invoices, payments, expenses, and statistics.', 'my-easy-compta'), 'https://myeasycompta.com/addons-all/myeasycompta-export/');?>
        <?php $this->render_addon_card('my-easy-compta-planning', __('myEasyCompta Planning', 'my-easy-compta'), __('Integrate a planning module to manage your schedule. Create and assign tasks with specific categories, and track project deadlines and workloads efficiently.', 'my-easy-compta'), 'https://myeasycompta.com/addons-all/myeasycompta-planning/');?>
        <?php $this->render_addon_card('my-easy-compta-stats', __('myEasyCompta Stats', 'my-easy-compta'), __('Get detailed statistical analysis of your financial data. Generate various reports on income, expenses, and profitability with graphical representations for easier interpretation.', 'my-easy-compta'), 'https://myeasycompta.com/addons-all/myeasycompta-stats/');?>
        <?php //$this->render_addon_card('my-easy-compta-support', __('myEasyCompta Support', 'my-easy-compta'), __('Integrate a support ticket system to manage customer support requests. Create, track, and resolve tickets, prioritize tasks, and ensure timely responses.', 'my-easy-compta'), 'https://myeasycompta.com/addons-all/myeasycompta-support/');?>
        <?php $this->render_addon_card('my-easy-compta-user', __('myEasyCompta User', 'my-easy-compta'), __('Create secure access for your customers. Allow them to view quotes, invoices, payments, and statistics, and update their information and passwords through a dedicated dashboard.', 'my-easy-compta'), 'https://myeasycompta.com/addons-all/myeasycompta-compte-client/');?>
        <?php $this->render_addon_card('my-easy-compta-siret', __('myEasyCompta SIRET Search', 'my-easy-compta'), __('The Easy-Compta SIRET plugin adds a field to quickly retrieve customer information using the French government business directory API (https://api.gouv.fr). This enhances the efficiency of managing client data within the Easy-Compta system.', 'my-easy-compta'), 'https://myeasycompta.com/addons-all/myeasycompta-siret/');?>
        <?php $this->render_addon_card('my-easy-compta-woo', __('myEasyCompta Woo', 'my-easy-compta'), __('Integrate myEasyCompta with WooCommerce to automatically generate invoices for each new order. Sync WooCommerce sales data, manage transactions, track sales, and handle customer invoicing seamlessly.', 'my-easy-compta'), 'https://myeasycompta.com/addons-all/myeasycompta-woo/');?>
        <?php $this->render_addon_card('my-easy-compta-backup', __('myEasyCompta Backup', 'my-easy-compta'), __('myEasyCompta Backup plugin provides integrated solutions for securely backing up, restoring, and managing financial data.', 'my-easy-compta'), 'https://myeasycompta.com/addons-all/myeasycompta-backup/');?>
        <?php $this->render_addon_card('my-easy-compta-signature', __('myEasyCompta Signature', 'my-easy-compta'), __('myEasyCompta Signature allows myEasyCompta users to easily collect digital signatures on quotes, ensuring secure and efficient document approval.', 'my-easy-compta'), 'https://myeasycompta.com/addons-all/myeasycompta-signature/');?>
    </div>
</div>
<?php
}

    private function render_addon_card($slug, $title, $description, $link)
    {
        ?>
<div class="card p-6 bg-base-100 shadow-xl flex flex-col">
    <div class="card-body flex flex-col flex-grow">
        <h2 class="card-title mb-2"><?php esc_html_e($title);?></h2>
        <p class="mb-2 flex-grow"><?php esc_html_e($description);?></p>
        <div class="flex card-actions justify-end mt-auto btn-rounded">
            <a class="btn btn-primary" href="<?php esc_html_e($link);?>"
                target="_blank"><?php esc_html_e('Voir plus', 'my-easy-compta');?></a>
        </div>
    </div>
</div>
<?php
}

}