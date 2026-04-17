<?php

namespace ECWP\EInvoicing\Workflow;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface PDPInterface
 * 
 * Contrat que doivent respecter les addons de connexion PDP (ex: myeasycompta-pdp).
 */
interface PDPInterface
{
    /**
     * Retourne le nom du provider (ex: "Chorus Pro", "JeFacture.com")
     */
    public function getName(): string;

    /**
     * Retourne l'identifiant unique du provider (ex: "chorus", "jefacture")
     */
    public function getId(): string;

    /**
     * Vérifie si le provider est configuré et prêt
     */
    public function isReady(): bool;

    /**
     * Transmet une facture au format Factur-X
     * 
     * @param int $invoiceId L'ID de la facture WP
     * @param string $xmlContent Le contenu XML de la facture
     * @param string $pdfPath Le chemin vers le PDF
     * @return array Résultat ['success' => bool, 'message' => string, 'transmission_id' => string]
     */
    public function transmit(int $invoiceId, string $xmlContent, string $pdfPath): array;

    /**
     * Récupère le statut d'une transmission
     * 
     * @param string $transmissionId
     * @return array ['status' => string, 'details' => string]
     */
    public function getStatus(string $transmissionId): array;
}
