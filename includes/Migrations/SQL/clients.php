<?php

if (!defined('ABSPATH')) {
    exit;
}
global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS " . ECWP_TABLE_CLIENTS . " (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    siren_number BIGINT(14) NOT NULL DEFAULT 0,
    tax_number VARCHAR(255) NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    manager_name VARCHAR(255) NOT NULL,
    address TEXT,
    city VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100),
    phone VARCHAR(20),
    mobile_phone VARCHAR(20),
    email VARCHAR(100) NOT NULL,
    website VARCHAR(255),
    currency_id mediumint(9),
    note TEXT,
    PRIMARY KEY  (id)
    ) $charset_collate;";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta($sql);
