<?php
/**
 * Uninstall myEasyCompta
 *
 * Triggered when the user clicks "Delete" in the WordPress plugins page.
 * Removes all plugin tables, options, transients and scheduled events.
 *
 * @package myEasyCompta
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// ── 1. Drop all plugin tables ──────────────────────────────────────────────────
// Use SHOW TABLES to catch any ecwp_ table regardless of the DB prefix,
// including tables created by addons or future migrations.
$prefix  = $wpdb->prefix . 'ecwp_';
$tables  = $wpdb->get_col(
    $wpdb->prepare(
        'SHOW TABLES LIKE %s',
        $wpdb->esc_like($prefix) . '%'
    )
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS `{$table}`"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// ── 2. Delete all plugin options ───────────────────────────────────────────────
$options = $wpdb->get_col(
    $wpdb->prepare(
        "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
        $wpdb->esc_like('ecwp_') . '%'
    )
);

foreach ($options as $option) {
    delete_option($option);
}

// ── 3. Delete all plugin transients ────────────────────────────────────────────
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        $wpdb->esc_like('_transient_ecwp_') . '%',
        $wpdb->esc_like('_transient_timeout_ecwp_') . '%'
    )
);

// ── 4. Unschedule cron events ──────────────────────────────────────────────────
$cron_hooks = [
    'ecwp_invoice_reminders_daily',
];

foreach ($cron_hooks as $hook) {
    $timestamp = wp_next_scheduled($hook);
    if ($timestamp) {
        wp_unschedule_event($timestamp, $hook);
    }
    wp_clear_scheduled_hook($hook);
}

// ── 5. Flush rewrite rules ─────────────────────────────────────────────────────
flush_rewrite_rules();
