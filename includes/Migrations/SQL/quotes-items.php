<?php

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS " . ECWP_TABLE_QUOTE_ELEMENTS . " (
    id int(11) NOT NULL AUTO_INCREMENT,
    quote_id int(11) NOT NULL,
    item_name varchar(255) NOT NULL,
    item_ref varchar(255) NOT NULL,
    item_category int(11) NOT NULL,
    item_description longtext NOT NULL,
    quantity varchar(255) NOT NULL,
    vat_rate int(11) NOT NULL,
    unit_price decimal(10,2) NOT NULL,
    discount int(11) DEFAULT NULL,
    total_price decimal(10,2) NOT NULL,
    total_amount decimal(10,2) NOT NULL,
    item_order int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (id),
    KEY quote_id (quote_id)
) $charset_collate;";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta($sql);
