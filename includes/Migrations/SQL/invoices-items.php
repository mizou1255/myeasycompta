<?php

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS " . ECWP_TABLE_INVOICE_ELEMENTS . " (
    id int(11) NOT NULL AUTO_INCREMENT,
    invoice_id int(11) NOT NULL,
    item_name varchar(255) NOT NULL,
    item_ref varchar(255) NOT NULL,
    item_category int(11) NOT NULL,
    item_description LONGTEXT NOT NULL,
    quantity varchar(255) NOT NULL,
    vat_rate varchar(255) NOT NULL,
    unit_price varchar(255) NOT NULL,
    discount varchar(255) NOT NULL,
    total_price varchar(255) NOT NULL,
    total_amount varchar(255) NOT NULL,
    item_order int(11) NOT NULL,
    PRIMARY KEY (id),
    KEY invoice_id (invoice_id)
) $charset_collate;";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta($sql);