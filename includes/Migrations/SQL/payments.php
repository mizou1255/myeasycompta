<?php

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS " . ECWP_TABLE_PAYMENTS . " (
    id int(11) NOT NULL AUTO_INCREMENT,
    invoice_id int(11) NOT NULL,
    client_id int(11) NOT NULL,
    amount decimal(10,2) NOT NULL,
    payment_method_id int(11) NOT NULL,
    payment_date date NOT NULL,
    notes text,
    PRIMARY KEY (id),
    KEY payment_method_id (payment_method_id)
) $charset_collate;";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta($sql);
