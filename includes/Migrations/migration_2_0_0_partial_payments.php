<?php
/**
 * Migration 2.0.0 - Paiements partiels
 *
 * Ajoute la colonne `paid_amount` sur la table ecwp_invoices afin de
 * permettre le suivi des paiements partiels (acomptes, règlements échelonnés).
 * Le statut de facture bénéficie d'un nouveau état : 'partial'.
 *
 * @package myEasyCompta
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

function run_migration_2_0_0()
{
    global $wpdb;

    $invoices_table = ECWP_TABLE_INVOICES;

    // Vérifier que la table existe
    if ($wpdb->get_var("SHOW TABLES LIKE '{$invoices_table}'") !== $invoices_table) {
        return;
    }

    $columns = $wpdb->get_col("DESCRIBE {$invoices_table}");

    // Ajouter paid_amount si absent
    if (!in_array('paid_amount', $columns, true)) {
        $wpdb->query(
            "ALTER TABLE `{$invoices_table}`
             ADD COLUMN `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00
             AFTER `total_amount`"
        );
    }

    // Synchroniser paid_amount pour les factures déjà marquées comme payées
    // (elles n'ont pas forcément de paiement enregistré — on met le montant total déchiffré
    //  via un marqueur : pour les 'paid' existantes, paid_amount = 0 est OK car le statut
    //  reste 'paid'. La sync réelle se fait lors du prochain ajout/édition de paiement.)
    // On initialise uniquement les factures 'paid' qui ont des paiements en DB
    $payments_table = ECWP_TABLE_PAYMENTS;
    $wpdb->query(
        "UPDATE `{$invoices_table}` i
         SET i.paid_amount = (
             SELECT COALESCE(SUM(p.amount), 0)
             FROM `{$payments_table}` p
             WHERE p.invoice_id = i.id
         )
         WHERE i.paid_amount = 0"
    );
}
