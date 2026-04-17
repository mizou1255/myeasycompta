<?php
/**
 * Migration 2.4.0 - Archivage des clients
 *
 * Ajoute la colonne `archived TINYINT(1) DEFAULT 0` sur ecwp_clients
 * pour permettre de masquer les clients inactifs sans les supprimer.
 *
 * @package myEasyCompta
 * @since 2.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

function run_migration_2_4_0()
{
    global $wpdb;

    $table = ECWP_TABLE_CLIENTS;

    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
        return;
    }

    $columns = $wpdb->get_col("DESCRIBE {$table}");

    if (!in_array('archived', $columns, true)) {
        $wpdb->query(
            "ALTER TABLE `{$table}`
             ADD COLUMN `archived` TINYINT(1) NOT NULL DEFAULT 0"
        );
    }
}
