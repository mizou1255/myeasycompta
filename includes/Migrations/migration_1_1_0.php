<?php
if (!defined('ABSPATH')) {
    exit;
}

function run_migration_1_1_0()
{
    global $wpdb;

    $column_exists = $wpdb->get_var(
        $wpdb->prepare(
            "SHOW COLUMNS FROM %i LIKE %s",
            ECWP_TABLE_CLIENTS, 'tax_number'
        )
    );

    if (is_null($column_exists)) {
        $wpdb->query($wpdb->prepare(
            "ALTER TABLE %i ADD COLUMN `tax_number` VARCHAR(255) NOT NULL AFTER `siren_number`",
            ECWP_TABLE_CLIENTS
        ));
    }

    $settings_data = array(
        array('meta_key' => 'logo_mentions_active', 'meta_value' => '1'),
        array('meta_key' => 'payment_conditions', 'meta_value' => '45 jours'),
        array('meta_key' => 'payment_mode', 'meta_value' => 'Virement bancaire'),
        array('meta_key' => 'invoice_iban', 'meta_value' => 'FR111 1111 1111 1111 1111 1111'),
        array('meta_key' => 'invoice_bic', 'meta_value' => 'BC111111111X'),
        array('meta_key' => 'show_phone', 'meta_value' => '1'),
        array('meta_key' => 'show_email', 'meta_value' => '1'),
        array('meta_key' => 'show_siren', 'meta_value' => '1'),
        array('meta_key' => 'show_tax_number', 'meta_value' => '1'),
        array('meta_key' => 'show_watermark', 'meta_value' => '1'),
        array('meta_key' => 'show_watermark_only_paid', 'meta_value' => '1'),
    );

    foreach ($settings_data as $data) {
        $wpdb->insert(ECWP_TABLE_SETTINGS, $data);
    }
}
