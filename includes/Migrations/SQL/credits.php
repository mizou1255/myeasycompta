<?php

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS " . ECWP_TABLE_CREDITS . " (
    id int(11) NOT NULL AUTO_INCREMENT,
    credit_number varchar(255) NOT NULL,
    invoice_id int(11) NOT NULL,
    created_at date DEFAULT NULL,
    PRIMARY KEY (id)
) $charset_collate;";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta($sql);
