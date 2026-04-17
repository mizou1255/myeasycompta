<?php
/**
 * Migration 1.5.0 - Facturation Électronique (OD-Ready)
 * 
 * IMPORTANT JURIDIQUE :
 * MyEasyCompta est un Opérateur de Dématérialisation (OD), pas une Plateforme Agréée (PDP).
 * Cette migration prépare la structure pour la facturation électronique française 2026.
 * La transmission fiscale se fait EXCLUSIVEMENT via une PDP externe agréée.
 * 
 * Sources légales :
 * - https://www.impots.gouv.fr/facturation-electronique
 * - https://www.economie.gouv.fr/cedef/facturation-electronique-entreprises
 * 
 * @package myEasyCompta
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Exécute la migration 1.5.0 pour la facturation électronique
 * 
 * @return void
 */
function run_migration_1_5_0()
{
    global $wpdb;
    
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // 1. Étendre la table ecwp_invoices
    extend_invoices_table($wpdb, $charset_collate);
    
    // 2. Créer table ecwp_invoice_fiscal_transmissions
    create_fiscal_transmissions_table($wpdb, $charset_collate);
    
    // 3. Créer table ecwp_invoice_fiscal_logs
    create_fiscal_logs_table($wpdb, $charset_collate);
    
    // 4. Créer table ecwp_mock_pdp_transmissions (SANDBOX uniquement)
    create_mock_pdp_transmissions_table($wpdb, $charset_collate);
    
    // 5. Étendre table ecwp_clients
    extend_clients_table($wpdb, $charset_collate);
    
    // 6. Étendre table ecwp_invoice_elements
    extend_invoice_elements_table($wpdb, $charset_collate);

    // 7. Créer table ecwp_invoice_history (historique général)
    create_invoice_history_table($wpdb, $charset_collate);
}

/**
 * Étend la table ecwp_invoices avec les colonnes fiscales
 * 
 * @param wpdb $wpdb
 * @param string $charset_collate
 * @return void
 */
function extend_invoices_table($wpdb, $charset_collate)
{
    $table_name = ECWP_TABLE_INVOICES;
    
    // Vérifier si les colonnes existent déjà
    $columns = $wpdb->get_col("DESCRIBE {$table_name}");
    
    $alterations = [];
    
    if (!in_array('fiscal_status', $columns)) {
        $alterations[] = "ADD COLUMN `fiscal_status` VARCHAR(50) DEFAULT 'draft' AFTER `status_stats`";
    }
    
    if (!in_array('fiscal_status_updated_at', $columns)) {
        $alterations[] = "ADD COLUMN `fiscal_status_updated_at` DATETIME NULL AFTER `fiscal_status`";
    }
    
    if (!in_array('validated_at', $columns)) {
        $alterations[] = "ADD COLUMN `validated_at` DATETIME NULL AFTER `fiscal_status_updated_at`";
    }
    
    if (!in_array('validated_by', $columns)) {
        $alterations[] = "ADD COLUMN `validated_by` BIGINT(20) UNSIGNED NULL AFTER `validated_at`";
    }
    
    if (!in_array('transaction_type', $columns)) {
        $alterations[] = "ADD COLUMN `transaction_type` VARCHAR(10) DEFAULT 'B2B' AFTER `validated_by`";
    }
    
    if (!in_array('original_invoice_id', $columns)) {
        $alterations[] = "ADD COLUMN `original_invoice_id` INT(11) NULL AFTER `transaction_type`";
    }
    
    if (!in_array('pdp_transmission_id', $columns)) {
        $alterations[] = "ADD COLUMN `pdp_transmission_id` VARCHAR(255) NULL AFTER `original_invoice_id`";
    }
    
    if (!in_array('pdp_name', $columns)) {
        $alterations[] = "ADD COLUMN `pdp_name` VARCHAR(100) NULL AFTER `pdp_transmission_id`";
    }
    
    if (!in_array('pdp_rejection_reason', $columns)) {
        $alterations[] = "ADD COLUMN `pdp_rejection_reason` TEXT NULL AFTER `pdp_name`";
    }
    
    if (!in_array('facturx_profile', $columns)) {
        $alterations[] = "ADD COLUMN `facturx_profile` ENUM('minimum', 'basicwl', 'en16931', 'extended') DEFAULT 'extended' AFTER `pdp_rejection_reason`";
    }
    
    if (!in_array('facturx_hash', $columns)) {
        $alterations[] = "ADD COLUMN `facturx_hash` VARCHAR(64) NULL AFTER `facturx_profile`";
    }
    
    if (!in_array('facturx_pdf_path', $columns)) {
        $alterations[] = "ADD COLUMN `facturx_pdf_path` VARCHAR(500) NULL AFTER `facturx_hash`";
    }
    
    if (!empty($alterations)) {
        $sql = "ALTER TABLE `{$table_name}` " . implode(', ', $alterations);
        $wpdb->query($sql);
    }
    
    // Créer les index
    $indexes = $wpdb->get_results("SHOW INDEX FROM {$table_name}");
    $index_names = array_column($indexes, 'Key_name');
    
    if (!in_array('idx_fiscal_status', $index_names)) {
        $wpdb->query("CREATE INDEX `idx_fiscal_status` ON `{$table_name}` (`fiscal_status`)");
    }
    
    if (!in_array('idx_pdp_transmission_id', $index_names)) {
        $wpdb->query("CREATE INDEX `idx_pdp_transmission_id` ON `{$table_name}` (`pdp_transmission_id`)");
    }
    
    if (!in_array('idx_validated_at', $index_names)) {
        $wpdb->query("CREATE INDEX `idx_validated_at` ON `{$table_name}` (`validated_at`)");
    }
    
    if (!in_array('idx_facturx_profile', $index_names)) {
        $wpdb->query("CREATE INDEX `idx_facturx_profile` ON `{$table_name}` (`facturx_profile`)");
    }
    
    // Ajouter les foreign keys si elles n'existent pas
    // Note: WordPress peut ne pas supporter toutes les FK, on les ajoute conditionnellement
    // Vérifier si la contrainte existe déjà pour éviter les erreurs
    $fk_validated_by = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) 
        FROM information_schema.TABLE_CONSTRAINTS 
        WHERE CONSTRAINT_SCHEMA = DATABASE()
        AND TABLE_NAME = %s
        AND CONSTRAINT_NAME = 'fk_invoices_validated_by'
    ", $table_name));
    
    if (!$fk_validated_by && $wpdb->users) {
        // Essayer d'ajouter la FK, ignorer l'erreur si elle existe déjà
        @$wpdb->query("
            ALTER TABLE `{$table_name}`
            ADD CONSTRAINT `fk_invoices_validated_by` 
            FOREIGN KEY (`validated_by`) REFERENCES `{$wpdb->users}`(`ID`) 
            ON DELETE SET NULL
        ");
    }
    
    $fk_original = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) 
        FROM information_schema.TABLE_CONSTRAINTS 
        WHERE CONSTRAINT_SCHEMA = DATABASE()
        AND TABLE_NAME = %s
        AND CONSTRAINT_NAME = 'fk_invoices_original'
    ", $table_name));
    
    if (!$fk_original) {
        // Essayer d'ajouter la FK, ignorer l'erreur si elle existe déjà
        @$wpdb->query("
            ALTER TABLE `{$table_name}`
            ADD CONSTRAINT `fk_invoices_original` 
            FOREIGN KEY (`original_invoice_id`) REFERENCES `{$table_name}`(`id`) 
            ON DELETE SET NULL
        ");
    }
}

/**
 * Crée la table ecwp_invoice_fiscal_transmissions
 * 
 * Historique complet des transmissions vers les PDP
 * 
 * @param wpdb $wpdb
 * @param string $charset_collate
 * @return void
 */
function create_fiscal_transmissions_table($wpdb, $charset_collate)
{
    $table_name = ECWP_TABLE_INVOICE_FISCAL_TRANSMISSIONS;
    
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
        $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
            `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `invoice_id` INT(11) NOT NULL,
            `pdp_name` VARCHAR(100) NOT NULL,
            `pdp_transmission_id` VARCHAR(255) NULL,
            `transmission_status` VARCHAR(50) NOT NULL DEFAULT 'pending',
            `facturx_xml` LONGTEXT NULL,
            `facturx_pdf_path` VARCHAR(500) NULL,
            `transmitted_at` DATETIME NULL,
            `acknowledgment_receipt` TEXT NULL,
            `rejection_reason` TEXT NULL,
            `error_message` TEXT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_invoice_id` (`invoice_id`),
            KEY `idx_pdp_transmission_id` (`pdp_transmission_id`),
            KEY `idx_transmission_status` (`transmission_status`),
            KEY `idx_transmitted_at` (`transmitted_at`),
            FOREIGN KEY (`invoice_id`) REFERENCES `" . ECWP_TABLE_INVOICES . "`(`id`) ON DELETE CASCADE
        ) {$charset_collate};";
        
        dbDelta($sql);
    }
}

/**
 * Crée la table ecwp_invoice_fiscal_logs
 * 
 * Journal immuable des actions fiscales sensibles
 * 
 * @param wpdb $wpdb
 * @param string $charset_collate
 * @return void
 */
function create_fiscal_logs_table($wpdb, $charset_collate)
{
    $table_name = ECWP_TABLE_INVOICE_FISCAL_LOGS;
    
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
        $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
            `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `invoice_id` INT(11) NOT NULL,
            `action` VARCHAR(100) NOT NULL,
            `user_id` BIGINT(20) UNSIGNED NULL,
            `old_value` TEXT NULL,
            `new_value` TEXT NULL,
            `ip_address` VARCHAR(45) NULL,
            `user_agent` VARCHAR(255) NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_invoice_id` (`invoice_id`),
            KEY `idx_action` (`action`),
            KEY `idx_created_at` (`created_at`),
            FOREIGN KEY (`invoice_id`) REFERENCES `" . ECWP_TABLE_INVOICES . "`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`user_id`) REFERENCES `{$wpdb->users}`(`ID`) ON DELETE SET NULL
        ) {$charset_collate};";
        
        dbDelta($sql);
    }
}

/**
 * Crée la table ecwp_mock_pdp_transmissions
 * 
 * Table pour stocker les transmissions Mock PDP (SANDBOX UNIQUEMENT)
 * 
 * ⚠️ IMPORTANT JURIDIQUE :
 * Cette table est UNIQUEMENT pour les tests de développement.
 * Elle n'a AUCUNE valeur fiscale.
 * 
 * @param wpdb $wpdb
 * @param string $charset_collate
 * @return void
 */
function create_mock_pdp_transmissions_table($wpdb, $charset_collate)
{
    $table_name = ECWP_TABLE_MOCK_PDP_TRANSMISSIONS;
    
    // Vérifier si la table existe déjà
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
    
    if (!$table_exists) {
        $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `transmission_id` varchar(100) NOT NULL,
            `invoice_id` bigint(20) UNSIGNED NOT NULL,
            `status` varchar(50) NOT NULL DEFAULT 'pending',
            `facturx_xml` longtext DEFAULT NULL,
            `facturx_pdf_path` varchar(500) DEFAULT NULL,
            `error_message` text DEFAULT NULL,
            `acknowledgment_receipt` longtext DEFAULT NULL,
            `simulated_delay` int(11) DEFAULT 5,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `transmission_id` (`transmission_id`),
            KEY `invoice_id` (`invoice_id`),
            KEY `status` (`status`),
            KEY `created_at` (`created_at`)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Vérifier que la table a bien été créée
        $table_created = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
        if (!$table_created) {
            // Tentative directe si dbDelta a échoué
            $wpdb->query($sql);
        }
    }
}

/**
 * Étend la table ecwp_clients avec les colonnes e-invoicing
 * 
 * @param wpdb $wpdb
 * @param string $charset_collate
 * @return void
 */
function extend_clients_table($wpdb, $charset_collate)
{
    $table_name = ECWP_TABLE_CLIENTS;
    
    $columns = $wpdb->get_col("DESCRIBE {$table_name}");
    
    $alterations = [];
    
    if (!in_array('client_type', $columns)) {
        $alterations[] = "ADD COLUMN `client_type` VARCHAR(10) DEFAULT 'B2B' AFTER `country`";
    }
    
    if (!in_array('vat_number', $columns)) {
        $alterations[] = "ADD COLUMN `vat_number` VARCHAR(50) NULL AFTER `tax_number`";
    }
    
    if (!empty($alterations)) {
        $sql = "ALTER TABLE `{$table_name}` " . implode(', ', $alterations);
        $wpdb->query($sql);
    }
    
    // Index
    $indexes = $wpdb->get_results("SHOW INDEX FROM {$table_name}");
    $index_names = array_column($indexes, 'Key_name');
    
    if (!in_array('idx_client_type', $index_names)) {
        $wpdb->query("CREATE INDEX `idx_client_type` ON `{$table_name}` (`client_type`)");
    }
}

/**
 * Étend la table ecwp_invoice_elements avec les colonnes e-invoicing
 * 
 * @param wpdb $wpdb
 * @param string $charset_collate
 * @return void
 */
function extend_invoice_elements_table($wpdb, $charset_collate)
{
    $table_name = ECWP_TABLE_INVOICE_ELEMENTS;
    
    $columns = $wpdb->get_col("DESCRIBE {$table_name}");
    
    $alterations = [];
    
    if (!in_array('unit_code', $columns)) {
        $alterations[] = "ADD COLUMN `unit_code` VARCHAR(10) DEFAULT 'C62' AFTER `quantity`";
    }
    
    if (!in_array('classification_code', $columns)) {
        $alterations[] = "ADD COLUMN `classification_code` VARCHAR(50) NULL AFTER `item_category`";
    }
    
    if (!empty($alterations)) {
        $sql = "ALTER TABLE `{$table_name}` " . implode(', ', $alterations);
        $wpdb->query($sql);
    }
}

/**
 * Crée la table ecwp_invoice_history
 * 
 * Historique général de toutes les modifications d'une facture
 * (ajout/suppression/modification d'éléments, modification de prix, etc.)
 * 
 * @param wpdb $wpdb
 * @param string $charset_collate
 * @return void
 */
function create_invoice_history_table($wpdb, $charset_collate)
{
    $table_name = ECWP_PREFIX . 'ecwp_invoice_history';
    
    // Vérifier si la table existe déjà
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name) {
        return; // Table existe déjà
    }
    
    // Créer la table sans clés étrangères d'abord (pour éviter les erreurs)
    // Note: Pas de COMMENT dans les colonnes pour éviter les problèmes d'échappement SQL
    $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `invoice_id` INT(11) NOT NULL,
        `action` VARCHAR(100) NOT NULL,
        `entity_type` VARCHAR(50) NULL,
        `entity_id` INT(11) NULL,
        `user_id` BIGINT(20) UNSIGNED NULL,
        `old_value` TEXT NULL,
        `new_value` TEXT NULL,
        `description` TEXT NULL,
        `ip_address` VARCHAR(45) NULL,
        `user_agent` VARCHAR(255) NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_invoice_id` (`invoice_id`),
        KEY `idx_action` (`action`),
        KEY `idx_entity_type` (`entity_type`),
        KEY `idx_created_at` (`created_at`)
    ) {$charset_collate};";
    
    // Utiliser $wpdb->query() directement au lieu de dbDelta() pour plus de contrôle
    $result = $wpdb->query($sql);
    
    if ($result === false) {
        // Log l'erreur
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Migration 1.5.0 - Failed to create table {$table_name}: " . $wpdb->last_error);
        }
        return;
    }
    
    // Ajouter les clés étrangères après la création de la table (si supportées)
    // Note: Les clés étrangères peuvent échouer si le moteur de stockage ne les supporte pas (MyISAM)
    // On les ajoute conditionnellement pour ne pas bloquer la création
    
    // Vérifier si le moteur de stockage supporte les clés étrangères (InnoDB)
    // Note: On essaie d'ajouter les FK même si on ne peut pas vérifier le moteur
    // car certaines configurations peuvent avoir des problèmes avec information_schema
    $db_name = DB_NAME;
    $engine = $wpdb->get_var($wpdb->prepare(
        "SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s",
        $db_name,
        $table_name
    ));
    
    // Si on ne peut pas déterminer le moteur, on essaie quand même (souvent InnoDB par défaut)
    if ($engine === 'InnoDB' || $engine === null) {
        // Ajouter la clé étrangère pour invoice_id
        $fk_invoice = $wpdb->query("
            ALTER TABLE `{$table_name}`
            ADD CONSTRAINT `fk_invoice_history_invoice_id`
            FOREIGN KEY (`invoice_id`) REFERENCES `" . ECWP_TABLE_INVOICES . "`(`id`) ON DELETE CASCADE
        ");
        
        if ($fk_invoice === false && defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Migration 1.5.0 - Failed to add FK for invoice_id: " . $wpdb->last_error);
        }
        
        // Ajouter la clé étrangère pour user_id
        $fk_user = $wpdb->query("
            ALTER TABLE `{$table_name}`
            ADD CONSTRAINT `fk_invoice_history_user_id`
            FOREIGN KEY (`user_id`) REFERENCES `{$wpdb->users}`(`ID`) ON DELETE SET NULL
        ");
        
        if ($fk_user === false && defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Migration 1.5.0 - Failed to add FK for user_id: " . $wpdb->last_error);
        }
    }
}


