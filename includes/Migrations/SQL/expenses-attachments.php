<?php

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS " . ECWP_TABLE_EXPENSES_ATTACHMENTS . " (
    id int(11) NOT NULL AUTO_INCREMENT,
    filename varchar(255) NOT NULL,
    type varchar(50),
    expense_id int(11),
    PRIMARY KEY (id)
) $charset_collate;";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta($sql);
