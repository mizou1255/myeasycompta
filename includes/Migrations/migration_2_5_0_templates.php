<?php
/**
 * Migration 2.5.0 - Templates de factures et devis
 *
 * Ajoute la colonne `is_template TINYINT(1) DEFAULT 0` sur les tables
 * ecwp_invoices et ecwp_quotes pour permettre de sauvegarder des modèles
 * réutilisables.
 *
 * @package myEasyCompta
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

function run_migration_2_5_0()
{
    global $wpdb;

    foreach ([ECWP_TABLE_INVOICES, ECWP_TABLE_QUOTES] as $table) {
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            continue;
        }
        $columns = $wpdb->get_col("DESCRIBE {$table}");
        if (!in_array('is_template', $columns, true)) {
            $wpdb->query(
                "ALTER TABLE `{$table}`
                 ADD COLUMN `is_template` TINYINT(1) NOT NULL DEFAULT 0"
            );
        }
    }
}
