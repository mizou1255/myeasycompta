<?php

if (!defined('ABSPATH')) exit;

function run_migration_2_2_0(): void
{
    global $wpdb;

    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS " . ECWP_TABLE_CONTRACTS . " (
        id              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        entity_id       INT(11) NOT NULL DEFAULT 1,
        client_id       INT(11) DEFAULT NULL,
        invoice_id      INT(11) DEFAULT NULL,
        quote_id        INT(11) DEFAULT NULL,
        title           VARCHAR(255) NOT NULL DEFAULT '',
        body            LONGTEXT NOT NULL,
        variables       LONGTEXT DEFAULT NULL COMMENT 'JSON object of custom variable values',
        status          VARCHAR(32) NOT NULL DEFAULT 'draft' COMMENT 'draft|sent|signed|archived',
        signed_at       DATETIME DEFAULT NULL,
        sent_at         DATETIME DEFAULT NULL,
        created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY entity_id (entity_id),
        KEY client_id (client_id),
        KEY status (status)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    update_option('ecwp_db_version', '2.2.0');
}

run_migration_2_2_0();
