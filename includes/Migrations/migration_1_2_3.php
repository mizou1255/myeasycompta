<?php
if (!defined('ABSPATH')) {
    exit;
}

function run_migration_1_2_3()
{
    global $wpdb;

    $table_name = ECWP_TABLE_QUOTE_ELEMENTS;

    $sql = "ALTER TABLE {$table_name} MODIFY `quantity` VARCHAR(255) NOT NULL;";

    $wpdb->query($sql);
}
