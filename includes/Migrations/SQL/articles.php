<?php

if (!defined('ABSPATH')) {
    exit;
}
global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS " . ECWP_TABLE_ARTICLES . " (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        ref varchar(255) NOT NULL,
        description text NOT NULL,
        unit_price decimal(10, 2) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

$sql_categories = "CREATE TABLE IF NOT EXISTS " . ECWP_TABLE_ARTICLES_CATEGORIES . " (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta($sql);
dbDelta($sql_categories);
