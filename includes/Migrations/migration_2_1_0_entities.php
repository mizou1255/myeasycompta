<?php
/**
 * Migration 2.1.0 — Multi-entreprise
 *
 * - Crée la table wp_ecwp_entities
 * - Ajoute entity_id aux tables invoices, quotes, payments, expenses, clients
 * - Insère une entité par défaut avec les données de Settings existantes
 */

function run_migration_2_1_0()
{
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    // ── 1. Créer la table entities ────────────────────────────────────────────
    $sql = "CREATE TABLE IF NOT EXISTS " . ECWP_TABLE_ENTITIES . " (
        id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        email varchar(100) NOT NULL DEFAULT '',
        phone varchar(30) NOT NULL DEFAULT '',
        address text,
        city varchar(100) NOT NULL DEFAULT '',
        postal_code varchar(20) NOT NULL DEFAULT '',
        country varchar(100) NOT NULL DEFAULT '',
        siret varchar(20) NOT NULL DEFAULT '',
        vat_number varchar(50) NOT NULL DEFAULT '',
        iban varchar(50) NOT NULL DEFAULT '',
        bic varchar(20) NOT NULL DEFAULT '',
        website varchar(255) NOT NULL DEFAULT '',
        logo_url varchar(500) NOT NULL DEFAULT '',
        pdf_color varchar(10) NOT NULL DEFAULT '#7c3aed',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // ── 2. Entité par défaut (récupère les données existantes de Settings) ────
    $existing = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . ECWP_TABLE_ENTITIES);
    if ($existing === 0) {
        // Lire les réglages actuels pour pré-remplir
        $get = function (string $key) use ($wpdb): string {
            $val = $wpdb->get_var(
                $wpdb->prepare("SELECT meta_value FROM " . ECWP_TABLE_SETTINGS . " WHERE meta_key = %s", $key)
            );
            return $val ? (string) $val : '';
        };

        $wpdb->insert(
            ECWP_TABLE_ENTITIES,
            [
                'name'       => $get('company_name') ?: get_bloginfo('name'),
                'email'      => $get('company_email'),
                'phone'      => $get('company_phone'),
                'address'    => $get('company_address'),
                'city'       => $get('company_city'),
                'postal_code'=> $get('company_postal_code'),
                'country'    => $get('company_country'),
                'siret'      => $get('company_siren'),
                'vat_number' => $get('company_vat_number'),
                'iban'       => $get('invoice_iban'),
                'bic'        => $get('invoice_bic'),
                'logo_url'   => $get('company_logo'),
                'pdf_color'  => $get('invoice_color') ?: '#7c3aed',
            ],
            ['%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s']
        );
    }

    $default_entity_id = (int) $wpdb->get_var("SELECT id FROM " . ECWP_TABLE_ENTITIES . " LIMIT 1");
    if (!$default_entity_id) $default_entity_id = 1;

    // ── 3. Ajouter entity_id aux tables principales ───────────────────────────
    $alter_map = [
        ECWP_TABLE_INVOICES  => 'id',
        ECWP_TABLE_QUOTES    => 'id',
        ECWP_TABLE_PAYMENTS  => 'id',
        ECWP_TABLE_EXPENSES  => 'id',
        ECWP_TABLE_CLIENTS   => 'id',
    ];

    foreach (array_keys($alter_map) as $table) {
        $col_exists = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'entity_id'",
                DB_NAME, $table)
        );
        if (!$col_exists) {
            $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN `entity_id` int(11) NOT NULL DEFAULT 1 AFTER `id`");
            // Remplir les lignes existantes avec l'entité par défaut
            $wpdb->query($wpdb->prepare("UPDATE `{$table}` SET entity_id = %d WHERE entity_id = 0 OR entity_id IS NULL", $default_entity_id));
        }
    }

    update_option('ecwp_db_version', '2.1.0');
}
