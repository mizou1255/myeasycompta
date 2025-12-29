<?php

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS " . ECWP_TABLE_CURRENCY . " (
    id int(11) NOT NULL AUTO_INCREMENT,
    code varchar(10) NOT NULL,
    symbol varchar(10) NOT NULL,
    name varchar(100),
    PRIMARY KEY (id)
) $charset_collate;";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta($sql);
