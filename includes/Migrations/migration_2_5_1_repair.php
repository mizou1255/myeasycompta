<?php
/**
 * Migration 2.5.1 - Repair: ensure all columns from 2.3.0-2.5.0 exist
 *
 * Re-checks and adds any columns that may have been missed by earlier
 * migrations due to partial failures (e.g. missing AFTER target column).
 *
 * @package myEasyCompta
 * @since 2.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

function run_migration_2_5_1()
{
    global $wpdb;

    // 2.3.0 — internal_notes on invoices and quotes
    foreach ([ECWP_TABLE_INVOICES, ECWP_TABLE_QUOTES] as $table) {
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            continue;
        }
        $columns = $wpdb->get_col("DESCRIBE {$table}");
        if (!in_array('internal_notes', $columns, true)) {
            $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN `internal_notes` TEXT NULL");
        }
        // 2.5.0 — is_template on invoices and quotes
        if (!in_array('is_template', $columns, true)) {
            $wpdb->query(
                "ALTER TABLE `{$table}`
                 ADD COLUMN `is_template` TINYINT(1) NOT NULL DEFAULT 0"
            );
        }
    }

    // 2.4.0 — archived on clients
    $clients_table = ECWP_TABLE_CLIENTS;
    if ($wpdb->get_var("SHOW TABLES LIKE '{$clients_table}'") === $clients_table) {
        $columns = $wpdb->get_col("DESCRIBE {$clients_table}");
        if (!in_array('archived', $columns, true)) {
            $wpdb->query(
                "ALTER TABLE `{$clients_table}`
                 ADD COLUMN `archived` TINYINT(1) NOT NULL DEFAULT 0"
            );
        }
    }
}
