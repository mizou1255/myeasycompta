<?php

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS " . ECWP_TABLE_INVOICES . " (
    id int(11) NOT NULL AUTO_INCREMENT,
    client_id int(11) NOT NULL,
    number int(11) NOT NULL,
    invoice_number varchar(255) NOT NULL,
    amount varchar(255) NOT NULL,
    total_amount varchar(255) NOT NULL,
    paid_amount decimal(10,2) NOT NULL DEFAULT 0.00,
    exchange_rate varchar(255),
    status varchar(255) NOT NULL,
    status_stats varchar(255) NOT NULL,
    credit int(11) NOT NULL,
    due_date date DEFAULT NULL,
    created_at date DEFAULT NULL,
    source varchar(255),
    fiscal_status varchar(50) DEFAULT 'draft',
    pdp_transmission_id varchar(255) DEFAULT NULL,
    pdp_name varchar(255) DEFAULT NULL,
    pdp_rejection_reason text DEFAULT NULL,
    PRIMARY KEY (id)
) $charset_collate;";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta($sql);
