<?php
/**
 * Migration 1.5.1 - Ajout colonne facturx_code sur ecwp_payments_methods
 *
 * Ajoute le code BT-81 (moyen de paiement EN 16931) par méthode de paiement,
 * requis pour générer un XML Factur-X conforme.
 *
 * @package myEasyCompta
 * @since 1.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Exécute la migration 1.5.1
 *
 * @return void
 */
function run_migration_1_5_1()
{
    global $wpdb;

    $table_name = ECWP_TABLE_PAYMENTS_METHODS;

    // Vérifier si la table existe
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
        return;
    }

    $columns = $wpdb->get_col("DESCRIBE {$table_name}");

    if (!in_array('facturx_code', $columns)) {
        $wpdb->query("ALTER TABLE `{$table_name}` ADD COLUMN `facturx_code` VARCHAR(10) DEFAULT '30' AFTER `method_name`");
    }

    // Mettre à jour les méthodes existantes avec les codes EN 16931 standard
    // 10=Espèces, 20=Chèque, 30=Virement, 48=Carte, 49=Prélèvement, 97=Non précisé
    $defaults = [
        'Virement'           => '30',
        'Virement bancaire'  => '30',
        'Carte'              => '48',
        'Carte bancaire'     => '48',
        'Carte de crédit'    => '48',
        'Espèces'            => '10',
        'Chèque'             => '20',
        'Prélèvement'        => '49',
        'PayPal'             => '97',
    ];

    foreach ($defaults as $name => $code) {
        $wpdb->query($wpdb->prepare(
            "UPDATE `{$table_name}` SET facturx_code = %s WHERE method_name = %s AND (facturx_code IS NULL OR facturx_code = '30')",
            $code,
            $name
        ));
    }
}
