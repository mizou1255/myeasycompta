<?php

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS " . ECWP_TABLE_EXPENSES . " (
    id int(11) NOT NULL AUTO_INCREMENT,
    amount decimal(10,2) NOT NULL,
    expense_date date NOT NULL,
    client_id int(11),
    category_id int(11) NOT NULL,
    attachment_id int(11),
    notes text,
    PRIMARY KEY (id),
    KEY client_id (client_id),
    KEY category_id (category_id),
    KEY attachment_id (attachment_id)
) $charset_collate;";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta($sql);
