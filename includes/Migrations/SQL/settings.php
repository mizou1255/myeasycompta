<?php

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS " . ECWP_TABLE_SETTINGS . " (
    id int(11) NOT NULL AUTO_INCREMENT,
    meta_key varchar(255) NOT NULL,
    meta_value longtext NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY meta_key (meta_key)
) $charset_collate;";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta($sql);
