<?php

namespace ECWP\Admin\ElectronicInvoice\Formats;

/**
 * UBL 2.1 Generator
 * 
 * Génère le XML UBL 2.1 conforme au standard OASIS UBL 2.1
 * pour la facturation électronique internationale
 */
class UBLGenerator
{
    /**
     * Génère le XML UBL 2.1 pour une facture
     * 
     * @param object $invoice Données de la facture
     * @param array $items Articles de la facture
     * @param object $client Données du client
     * @param object $seller Données du vendeur (settings)
     * @param object $currency Devise
     * @return string XML UBL 2.1
     */
    public function generateXML($invoice, $items, $client, $seller, $currency)
    {
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        
        // Décrypter les données
        $invoice_number = $encrypt->decrypt($invoice->invoice_number);
        $invoice_date = date('Y-m-d', strtotime($invoice->created_at));
        $due_date = date('Y-m-d', strtotime($invoice->due_date));
        $total_amount = floatval($encrypt->decrypt($invoice->total_amount));
        
        // Calculer les totaux
        $subtotal = 0;
        $total_vat = 0;
        $vat_breakdown = [];
        
        foreach ($items as $item) {
            $quantity = floatval($encrypt->decrypt($item->quantity));
            $unit_price = floatval($encrypt->decrypt($item->unit_price));
            $vat_rate = floatval($encrypt->decrypt($item->vat_rate));
            $discount = floatval($encrypt->decrypt($item->discount));
            
            $line_total = round($quantity * $unit_price, 2);
            $discount_amount = 0;
            if ($discount > 0) {
                $discount_amount = round($line_total * ($discount / 100), 2);
                $line_total = round($line_total - $discount_amount, 2);
            }
            
            $line_vat = round($line_total * ($vat_rate / 100), 2);
            $subtotal = round($subtotal + $line_total, 2);
            $total_vat = round($total_vat + $line_vat, 2);
            
            if (!isset($vat_breakdown[$vat_rate])) {
                $vat_breakdown[$vat_rate] = ['basis' => 0, 'vat' => 0];
            }
            $vat_breakdown[$vat_rate]['basis'] = round($vat_breakdown[$vat_rate]['basis'] + $line_total, 2);
            $vat_breakdown[$vat_rate]['vat'] = round($vat_breakdown[$vat_rate]['vat'] + $line_vat, 2);
        }
        
        // Récupérer les codes ISO
        $currency_code = $this->getCurrencyCode($currency);
        $seller_country = $this->getCountryCode($seller->country ?? 'FR');
        $buyer_country = $this->getCountryCode($client->country ?? 'FR');
        
        // Générer l'UUID
        $invoice_uuid = $this->generateUUID();
        
        // Construire le XML UBL 2.1
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" ';
        $xml .= 'xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" ';
        $xml .= 'xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">' . "\n";
        
        // ID de la facture
        $xml .= '  <cbc:ID>' . htmlspecialchars($invoice_number, ENT_XML1, 'UTF-8') . '</cbc:ID>' . "\n";
        
        // Date d'émission
        $xml .= '  <cbc:IssueDate>' . $invoice_date . '</cbc:IssueDate>' . "\n";
        
        // Type de document
        $xml .= '  <cbc:InvoiceTypeCode listID="UNCL1001">380</cbc:InvoiceTypeCode>' . "\n";
        
        // Devise
        $xml .= '  <cbc:DocumentCurrencyCode>' . $currency_code . '</cbc:DocumentCurrencyCode>' . "\n";
        
        // UUID de la facture
        $xml .= '  <cbc:UUID>' . $invoice_uuid . '</cbc:UUID>' . "\n";
        
        // Vendeur (Seller)
        $xml .= '  <cac:AccountingSupplierParty>' . "\n";
        $xml .= '    <cac:Party>' . "\n";
        $xml .= '      <cac:PartyIdentification>' . "\n";
        $xml .= '        <cbc:ID schemeID="SIREN">' . htmlspecialchars($seller->siret ?? '', ENT_XML1, 'UTF-8') . '</cbc:ID>' . "\n";
        $xml .= '      </cac:PartyIdentification>' . "\n";
        $xml .= '      <cac:PartyName>' . "\n";
        $xml .= '        <cbc:Name>' . htmlspecialchars($seller->company_name ?? '', ENT_XML1, 'UTF-8') . '</cbc:Name>' . "\n";
        $xml .= '      </cac:PartyName>' . "\n";
        $xml .= '      <cac:PostalAddress>' . "\n";
        $xml .= '        <cbc:StreetName>' . htmlspecialchars($seller->address ?? '', ENT_XML1, 'UTF-8') . '</cbc:StreetName>' . "\n";
        $xml .= '        <cbc:CityName>' . htmlspecialchars($seller->city ?? '', ENT_XML1, 'UTF-8') . '</cbc:CityName>' . "\n";
        $xml .= '        <cbc:PostalZone>' . htmlspecialchars($seller->postal_code ?? '', ENT_XML1, 'UTF-8') . '</cbc:PostalZone>' . "\n";
        $xml .= '        <cac:Country>' . "\n";
        $xml .= '          <cbc:IdentificationCode>' . $seller_country . '</cbc:IdentificationCode>' . "\n";
        $xml .= '        </cac:Country>' . "\n";
        $xml .= '      </cac:PostalAddress>' . "\n";
        if (!empty($seller->tax_number)) {
            $xml .= '      <cac:PartyTaxScheme>' . "\n";
            $xml .= '        <cac:TaxScheme>' . "\n";
            $xml .= '          <cbc:ID>VAT</cbc:ID>' . "\n";
            $xml .= '        </cac:TaxScheme>' . "\n";
            $xml .= '        <cbc:CompanyID>' . htmlspecialchars($seller->tax_number, ENT_XML1, 'UTF-8') . '</cbc:CompanyID>' . "\n";
            $xml .= '      </cac:PartyTaxScheme>' . "\n";
        }
        $xml .= '    </cac:Party>' . "\n";
        $xml .= '  </cac:AccountingSupplierParty>' . "\n";
        
        // Acheteur (Buyer)
        $xml .= '  <cac:AccountingCustomerParty>' . "\n";
        $xml .= '    <cac:Party>' . "\n";
        $xml .= '      <cac:PartyIdentification>' . "\n";
        $xml .= '        <cbc:ID>' . htmlspecialchars($client->company_name ?? '', ENT_XML1, 'UTF-8') . '</cbc:ID>' . "\n";
        $xml .= '      </cac:PartyIdentification>' . "\n";
        $xml .= '      <cac:PartyName>' . "\n";
        $xml .= '        <cbc:Name>' . htmlspecialchars($client->company_name ?? '', ENT_XML1, 'UTF-8') . '</cbc:Name>' . "\n";
        $xml .= '      </cac:PartyName>' . "\n";
        $xml .= '      <cac:PostalAddress>' . "\n";
        $xml .= '        <cbc:StreetName>' . htmlspecialchars($client->address ?? '', ENT_XML1, 'UTF-8') . '</cbc:StreetName>' . "\n";
        $xml .= '        <cbc:CityName>' . htmlspecialchars($client->city ?? '', ENT_XML1, 'UTF-8') . '</cbc:CityName>' . "\n";
        $xml .= '        <cbc:PostalZone>' . htmlspecialchars($client->postal_code ?? '', ENT_XML1, 'UTF-8') . '</cbc:PostalZone>' . "\n";
        $xml .= '        <cac:Country>' . "\n";
        $xml .= '          <cbc:IdentificationCode>' . $buyer_country . '</cbc:IdentificationCode>' . "\n";
        $xml .= '        </cac:Country>' . "\n";
        $xml .= '      </cac:PostalAddress>' . "\n";
        if (!empty($client->tax_number)) {
            $xml .= '      <cac:PartyTaxScheme>' . "\n";
            $xml .= '        <cac:TaxScheme>' . "\n";
            $xml .= '          <cbc:ID>VAT</cbc:ID>' . "\n";
            $xml .= '        </cac:TaxScheme>' . "\n";
            $xml .= '        <cbc:CompanyID>' . htmlspecialchars($client->tax_number, ENT_XML1, 'UTF-8') . '</cbc:CompanyID>' . "\n";
            $xml .= '      </cac:PartyTaxScheme>' . "\n";
        }
        $xml .= '    </cac:Party>' . "\n";
        $xml .= '  </cac:AccountingCustomerParty>' . "\n";
        
        // Lignes de facture
        $line_number = 1;
        foreach ($items as $item) {
            $quantity = floatval($encrypt->decrypt($item->quantity));
            $unit_price = floatval($encrypt->decrypt($item->unit_price));
            $vat_rate = floatval($encrypt->decrypt($item->vat_rate));
            $discount = floatval($encrypt->decrypt($item->discount));
            $item_name = $encrypt->decrypt($item->item_name);
            $item_description = $encrypt->decrypt($item->item_description ?? '');
            
            $line_total = round($quantity * $unit_price, 2);
            $discount_amount = 0;
            if ($discount > 0) {
                $discount_amount = round($line_total * ($discount / 100), 2);
                $line_total = round($line_total - $discount_amount, 2);
            }
            $line_vat = round($line_total * ($vat_rate / 100), 2);
            $line_total_with_vat = round($line_total + $line_vat, 2);
            
            $xml .= '    <cac:InvoiceLine>' . "\n";
            $xml .= '      <cbc:ID>' . $line_number . '</cbc:ID>' . "\n";
            $xml .= '      <cbc:InvoicedQuantity unitCode="C62">' . $quantity . '</cbc:InvoicedQuantity>' . "\n";
            $xml .= '      <cbc:LineExtensionAmount currencyID="' . $currency_code . '">' . number_format($line_total, 2, '.', '') . '</cbc:LineExtensionAmount>' . "\n";
            
            // Article
            $xml .= '      <cac:Item>' . "\n";
            $xml .= '        <cbc:Name>' . htmlspecialchars($item_name, ENT_XML1, 'UTF-8') . '</cbc:Name>' . "\n";
            if (!empty($item_description)) {
                $xml .= '        <cbc:Description>' . htmlspecialchars($item_description, ENT_XML1, 'UTF-8') . '</cbc:Description>' . "\n";
            }
            $xml .= '      </cac:Item>' . "\n";
            
            // Prix
            $xml .= '      <cac:Price>' . "\n";
            $xml .= '        <cbc:PriceAmount currencyID="' . $currency_code . '">' . number_format($unit_price, 2, '.', '') . '</cbc:PriceAmount>' . "\n";
            $xml .= '      </cac:Price>' . "\n";
            
            // TVA
            if ($vat_rate > 0) {
                $xml .= '      <cac:TaxTotal>' . "\n";
                $xml .= '        <cbc:TaxAmount currencyID="' . $currency_code . '">' . number_format($line_vat, 2, '.', '') . '</cbc:TaxAmount>' . "\n";
                $xml .= '        <cac:TaxSubtotal>' . "\n";
                $xml .= '          <cbc:TaxableAmount currencyID="' . $currency_code . '">' . number_format($line_total, 2, '.', '') . '</cbc:TaxableAmount>' . "\n";
                $xml .= '          <cbc:TaxAmount currencyID="' . $currency_code . '">' . number_format($line_vat, 2, '.', '') . '</cbc:TaxAmount>' . "\n";
                $xml .= '          <cac:TaxCategory>' . "\n";
                $xml .= '            <cbc:ID>S</cbc:ID>' . "\n";
                $xml .= '            <cbc:Percent>' . number_format($vat_rate, 2, '.', '') . '</cbc:Percent>' . "\n";
                $xml .= '            <cac:TaxScheme>' . "\n";
                $xml .= '              <cbc:ID>VAT</cbc:ID>' . "\n";
                $xml .= '            </cac:TaxScheme>' . "\n";
                $xml .= '          </cac:TaxCategory>' . "\n";
                $xml .= '        </cac:TaxSubtotal>' . "\n";
                $xml .= '      </cac:TaxTotal>' . "\n";
            }
            
            $xml .= '    </cac:InvoiceLine>' . "\n";
            $line_number++;
        }
        
        // Totaux
        $xml .= '  <cac:LegalMonetaryTotal>' . "\n";
        $xml .= '    <cbc:LineExtensionAmount currencyID="' . $currency_code . '">' . number_format($subtotal, 2, '.', '') . '</cbc:LineExtensionAmount>' . "\n";
        $xml .= '    <cbc:TaxExclusiveAmount currencyID="' . $currency_code . '">' . number_format($subtotal, 2, '.', '') . '</cbc:TaxExclusiveAmount>' . "\n";
        $xml .= '    <cbc:TaxInclusiveAmount currencyID="' . $currency_code . '">' . number_format($total_amount, 2, '.', '') . '</cbc:TaxInclusiveAmount>' . "\n";
        $xml .= '    <cbc:PayableAmount currencyID="' . $currency_code . '">' . number_format($total_amount, 2, '.', '') . '</cbc:PayableAmount>' . "\n";
        $xml .= '  </cac:LegalMonetaryTotal>' . "\n";
        
        // TVA globale
        if ($total_vat > 0) {
            $xml .= '  <cac:TaxTotal>' . "\n";
            $xml .= '    <cbc:TaxAmount currencyID="' . $currency_code . '">' . number_format($total_vat, 2, '.', '') . '</cbc:TaxAmount>' . "\n";
            foreach ($vat_breakdown as $rate => $breakdown) {
                $xml .= '    <cac:TaxSubtotal>' . "\n";
                $xml .= '      <cbc:TaxableAmount currencyID="' . $currency_code . '">' . number_format($breakdown['basis'], 2, '.', '') . '</cbc:TaxableAmount>' . "\n";
                $xml .= '      <cbc:TaxAmount currencyID="' . $currency_code . '">' . number_format($breakdown['vat'], 2, '.', '') . '</cbc:TaxAmount>' . "\n";
                $xml .= '      <cac:TaxCategory>' . "\n";
                $xml .= '        <cbc:ID>S</cbc:ID>' . "\n";
                $xml .= '        <cbc:Percent>' . number_format($rate, 2, '.', '') . '</cbc:Percent>' . "\n";
                $xml .= '        <cac:TaxScheme>' . "\n";
                $xml .= '          <cbc:ID>VAT</cbc:ID>' . "\n";
                $xml .= '        </cac:TaxScheme>' . "\n";
                $xml .= '      </cac:TaxCategory>' . "\n";
                $xml .= '    </cac:TaxSubtotal>' . "\n";
            }
            $xml .= '  </cac:TaxTotal>' . "\n";
        }
        
        $xml .= '</Invoice>';
        
        return $xml;
    }
    
    /**
     * Convertit le nom de devise en code ISO 4217
     */
    private function getCurrencyCode($currency)
    {
        if (is_object($currency) && isset($currency->code)) {
            return strtoupper($currency->code);
        }
        if (is_string($currency)) {
            return strtoupper($currency);
        }
        return 'EUR';
    }
    
    /**
     * Convertit le nom de pays en code ISO 3166-1 alpha-2
     */
    private function getCountryCode($country)
    {
        $countries = [
            'france' => 'FR',
            'italy' => 'IT',
            'spain' => 'ES',
            'germany' => 'DE',
            'belgium' => 'BE',
            'switzerland' => 'CH',
            'united kingdom' => 'GB',
            'united states' => 'US',
        ];
        
        $country_lower = strtolower($country);
        if (isset($countries[$country_lower])) {
            return $countries[$country_lower];
        }
        
        // Si c'est déjà un code à 2 lettres, le retourner
        if (strlen($country) === 2) {
            return strtoupper($country);
        }
        
        return 'FR'; // Par défaut
    }
    
    /**
     * Génère un UUID v4
     */
    private function generateUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}

