<?php
/**
 * Migration 2.3.0 - Notes internes
 *
 * Ajoute la colonne `internal_notes TEXT NULL` sur les tables
 * ecwp_invoices et ecwp_quotes pour permettre des mémos privés
 * (non affichés sur les PDFs).
 *
 * @package myEasyCompta
 * @since 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

function run_migration_2_3_0()
{
    global $wpdb;

    foreach ([ECWP_TABLE_INVOICES, ECWP_TABLE_QUOTES] as $table) {
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            continue;
        }
        $columns = $wpdb->get_col("DESCRIBE {$table}");
        if (!in_array('internal_notes', $columns, true)) {
            $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN `internal_notes` TEXT NULL");
        }
    }
}
