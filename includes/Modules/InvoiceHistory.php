<?php
/**
 * Invoice History
 * 
 * Gestion de l'historique général des modifications de factures
 * (ajout/suppression/modification d'éléments, modification de prix, etc.)
 * 
 * @package myEasyCompta
 * @subpackage Modules
 * @since 1.5.0
 */

namespace ECWP\Admin;

class InvoiceHistory
{
    /** @var bool|null Cached result of the table-exists check (null = unchecked) */
    private static $table_exists = null;

    /**
     * Returns true if the history table exists, caching the result for the request lifetime.
     */
    private static function table_exists(): bool
    {
        if (self::$table_exists !== null) {
            return self::$table_exists;
        }
        global $wpdb;
        $history_table = ECWP_TABLE_INVOICE_HISTORY;
        self::$table_exists = ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $history_table)) === $history_table);
        return self::$table_exists;
    }

    /**
     * Log une action dans l'historique
     *
     * @param int $invoice_id ID de la facture
     * @param string $action Type d'action (item_added, item_updated, item_deleted, price_updated, etc.)
     * @param string|null $entity_type Type d'entité (item, disbursement, invoice)
     * @param int|null $entity_id ID de l'entité modifiée
     * @param array|null $old_value Valeur avant modification
     * @param array|null $new_value Valeur après modification
     * @param string|null $description Description de l'action
     * @return void
     */
    public static function log(
        int $invoice_id,
        string $action,
        ?string $entity_type = null,
        ?int $entity_id = null,
        ?array $old_value = null,
        ?array $new_value = null,
        ?string $description = null
    ): void {
        if (!self::table_exists()) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("InvoiceHistory::log - Table " . ECWP_TABLE_INVOICE_HISTORY . " does not exist");
            }
            return;
        }

        global $wpdb;
        $history_table = ECWP_TABLE_INVOICE_HISTORY;

        // Anonymize IP: remove last octet (IPv4) or last group (IPv6) for GDPR compliance.
        $raw_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        $anon_ip = null;
        if ($raw_ip) {
            if (strpos($raw_ip, ':') !== false) {
                // IPv6 – zero the last 2 groups
                $anon_ip = preg_replace('/:[0-9a-fA-F]{0,4}:[0-9a-fA-F]{0,4}$/', ':0:0', $raw_ip);
            } else {
                // IPv4 – zero the last octet
                $anon_ip = preg_replace('/\.\d{1,3}$/', '.0', $raw_ip);
            }
        }

        $result = $wpdb->insert(
            $history_table,
            [
                'invoice_id' => $invoice_id,
                'action' => $action,
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'user_id' => get_current_user_id(),
                'old_value' => $old_value ? json_encode($old_value, JSON_UNESCAPED_UNICODE) : null,
                'new_value' => $new_value ? json_encode($new_value, JSON_UNESCAPED_UNICODE) : null,
                'description' => $description,
                'ip_address' => $anon_ip,
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : null,
            ],
            ['%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s']
        );
        
        if ($result === false && defined('WP_DEBUG') && WP_DEBUG) {
            error_log("InvoiceHistory::log - Failed to insert: " . $wpdb->last_error);
        }
    }
    
    /**
     * Récupère l'historique d'une facture
     * 
     * @param int $invoice_id
     * @return array
     */
    public static function get_history(int $invoice_id): array
    {
        if (!self::table_exists()) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("InvoiceHistory::get_history - Table " . ECWP_TABLE_INVOICE_HISTORY . " does not exist");
            }
            return [];
        }

        global $wpdb;
        $history_table = ECWP_TABLE_INVOICE_HISTORY;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$history_table} 
             WHERE invoice_id = %d 
             ORDER BY created_at DESC",
            $invoice_id
        ), ARRAY_A);
        
        if ($results === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("InvoiceHistory::get_history - Query failed: " . $wpdb->last_error);
            }
            return [];
        }
        
        // Décoder les valeurs JSON
        foreach ($results as &$result) {
            if (!empty($result['old_value'])) {
                $result['old_value'] = json_decode($result['old_value'], true);
            }
            if (!empty($result['new_value'])) {
                $result['new_value'] = json_decode($result['new_value'], true);
            }
        }
        
        return $results;
    }
}

