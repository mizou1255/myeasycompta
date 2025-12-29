<?php

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS " . ECWP_TABLE_QUOTES . " (
    id int(11) NOT NULL AUTO_INCREMENT,
    client_id int(11) NOT NULL,
    number int(11) NOT NULL,
    quote_number varchar(255) NOT NULL,
    amount decimal(10,2) NOT NULL,
    total_amount decimal(10,2) NOT NULL,
    status varchar(55) NOT NULL,
    due_date date DEFAULT NULL,
    provisional_start_date date DEFAULT NULL,
    created_at date DEFAULT NULL,
    converted int(11) NULL,
    PRIMARY KEY (id),
    KEY client_id (client_id)
) $charset_collate;";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta($sql);
