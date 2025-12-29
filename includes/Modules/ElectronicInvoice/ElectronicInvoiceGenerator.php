<?php

namespace ECWP\Admin\ElectronicInvoice;

use ECWP\Admin\ElectronicInvoice\Formats\UBLGenerator;
use ECWP\Admin\ElectronicInvoice\Formats\CIIGenerator;
use ECWP\Admin\ElectronicInvoice\Formats\ChorusProGenerator;
use ECWP\Admin\PDF\FacturXGenerator;

/**
 * Electronic Invoice Generator
 * 
 * Générateur principal pour les formats de facturation électronique
 * Supporte : Factur-X, UBL 2.1, CII, Chorus Pro
 */
class ElectronicInvoiceGenerator
{
    /**
     * Génère une facture électronique dans le format demandé
     * 
     * @param object $invoice Données de la facture
     * @param array $items Articles de la facture
     * @param object $client Données du client
     * @param object $seller Données du vendeur (settings)
     * @param object $currency Devise
     * @param string $format Format demandé (factur-x, ubl, cii, chorus-pro)
     * @return string XML de la facture électronique
     * @throws \Exception Si le format n'est pas supporté
     */
    public function generate($invoice, $items, $client, $seller, $currency, $format = 'factur-x')
    {
        switch (strtolower($format)) {
            case 'factur-x':
            case 'facturx':
                $generator = new FacturXGenerator();
                return $generator->generateXML($invoice, $items, $client, $seller, $currency);
                
            case 'ubl':
            case 'ubl2.1':
            case 'ubl-2.1':
                $generator = new UBLGenerator();
                return $generator->generateXML($invoice, $items, $client, $seller, $currency);
                
            case 'cii':
            case 'cii-d16b':
                $generator = new CIIGenerator();
                return $generator->generateXML($invoice, $items, $client, $seller, $currency);
                
            case 'chorus-pro':
            case 'choruspro':
            case 'chorus':
                $generator = new ChorusProGenerator();
                return $generator->generateXML($invoice, $items, $client, $seller, $currency);
                
            default:
                throw new \Exception(sprintf('Format non supporté : %s', $format));
        }
    }
    
    /**
     * Liste les formats disponibles
     * 
     * @return array Liste des formats supportés
     */
    public function getAvailableFormats()
    {
        return [
            'factur-x' => [
                'name' => 'Factur-X',
                'description' => 'Format Factur-X conforme EN 16931',
                'standard' => 'EN 16931',
                'country' => 'FR/EU'
            ],
            'ubl' => [
                'name' => 'UBL 2.1',
                'description' => 'Universal Business Language 2.1',
                'standard' => 'OASIS UBL 2.1',
                'country' => 'International'
            ],
            'cii' => [
                'name' => 'CII D16B',
                'description' => 'Cross Industry Invoice D16B',
                'standard' => 'UN/CEFACT CII D16B',
                'country' => 'International'
            ],
            'chorus-pro' => [
                'name' => 'Chorus Pro',
                'description' => 'Format Chorus Pro pour administration française',
                'standard' => 'Chorus Pro',
                'country' => 'FR'
            ]
        ];
    }
    
    /**
     * Valide un format de facture électronique
     * 
     * @param string $xml XML à valider
     * @param string $format Format de la facture
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validate($xml, $format)
    {
        // TODO: Implémenter la validation XSD selon le format
        // Pour l'instant, validation basique
        if (empty($xml)) {
            return [
                'valid' => false,
                'errors' => ['Le XML est vide']
            ];
        }
        
        // Vérifier que c'est du XML valide
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $loaded = $dom->loadXML($xml);
        
        if (!$loaded) {
            $errors = [];
            foreach (libxml_get_errors() as $error) {
                $errors[] = trim($error->message);
            }
            libxml_clear_errors();
            
            return [
                'valid' => false,
                'errors' => $errors
            ];
        }
        
        return [
            'valid' => true,
            'errors' => []
        ];
    }
}

