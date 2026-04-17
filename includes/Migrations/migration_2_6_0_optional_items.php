<?php
/**
 * Migration 2.6.0 - Optional items on quote elements
 *
 * Adds `is_optional` column to ecwp_quote_elements so each line item
 * can be flagged as mandatory (0) or optional (1).
 *
 * @package myEasyCompta
 * @since 2.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

function run_migration_2_6_0()
{
    global $wpdb;

    $table = ECWP_TABLE_QUOTE_ELEMENTS;
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
        return;
    }

    $columns = $wpdb->get_col("DESCRIBE {$table}");
    if (!in_array('is_optional', $columns, true)) {
        $wpdb->query(
            "ALTER TABLE `{$table}`
             ADD COLUMN `is_optional` TINYINT(1) NOT NULL DEFAULT 0"
        );
    }
}
