<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS " . ECWP_TABLE_DISBURSEMENTS . " (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    invoice_id BIGINT(20) UNSIGNED NOT NULL,
    ref VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    unit_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY invoice_id (invoice_id)
) {$charset_collate};";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta($sql);
