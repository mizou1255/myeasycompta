<?php

namespace ECWP\Admin\ElectronicInvoice\Formats;

/**
 * CII D16B Generator
 * 
 * Génère le XML CII D16B conforme au standard UN/CEFACT
 * pour la facturation électronique internationale
 */
class CIIGenerator
{
    /**
     * Génère le XML CII D16B pour une facture
     * 
     * @param object $invoice Données de la facture
     * @param array $items Articles de la facture
     * @param object $client Données du client
     * @param object $seller Données du vendeur (settings)
     * @param object $currency Devise
     * @return string XML CII D16B
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
        
        // Construire le XML CII D16B
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rsm:CrossIndustryInvoice xmlns:rsm="urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100" ';
        $xml .= 'xmlns:ram="urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100" ';
        $xml .= 'xmlns:udt="urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100">' . "\n";
        
        // ExchangedDocumentContext
        $xml .= '  <rsm:ExchangedDocumentContext>' . "\n";
        $xml .= '    <ram:GuidelineSpecifiedDocumentContextParameter>' . "\n";
        $xml .= '      <ram:ID>urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100</ram:ID>' . "\n";
        $xml .= '    </ram:GuidelineSpecifiedDocumentContextParameter>' . "\n";
        $xml .= '  </rsm:ExchangedDocumentContext>' . "\n";
        
        // ExchangedDocument
        $xml .= '  <rsm:ExchangedDocument>' . "\n";
        $xml .= '    <ram:ID>' . htmlspecialchars($invoice_number, ENT_XML1, 'UTF-8') . '</ram:ID>' . "\n";
        $xml .= '    <ram:TypeCode>380</ram:TypeCode>' . "\n";
        $xml .= '    <ram:IssueDateTime>' . "\n";
        $xml .= '      <udt:DateTimeString format="102">' . date('Ymd', strtotime($invoice_date)) . '</udt:DateTimeString>' . "\n";
        $xml .= '    </ram:IssueDateTime>' . "\n";
        $xml .= '  </rsm:ExchangedDocument>' . "\n";
        
        // SupplyChainTradeTransaction
        $xml .= '  <rsm:SupplyChainTradeTransaction>' . "\n";
        
        // IncludedSupplyChainTradeLineItem (lignes)
        foreach ($items as $index => $item) {
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
            
            $xml .= '    <ram:IncludedSupplyChainTradeLineItem>' . "\n";
            $xml .= '      <ram:AssociatedDocumentLineDocument>' . "\n";
            $xml .= '        <ram:LineID>' . ($index + 1) . '</ram:LineID>' . "\n";
            $xml .= '      </ram:AssociatedDocumentLineDocument>' . "\n";
            $xml .= '      <ram:SpecifiedTradeProduct>' . "\n";
            $xml .= '        <ram:Name>' . htmlspecialchars($item_name, ENT_XML1, 'UTF-8') . '</ram:Name>' . "\n";
            if (!empty($item_description)) {
                $xml .= '        <ram:Description>' . htmlspecialchars($item_description, ENT_XML1, 'UTF-8') . '</ram:Description>' . "\n";
            }
            $xml .= '      </ram:SpecifiedTradeProduct>' . "\n";
            $xml .= '      <ram:SpecifiedLineTradeAgreement>' . "\n";
            $xml .= '        <ram:NetPriceProductTradePrice>' . "\n";
            $xml .= '          <ram:ChargeAmount>' . number_format($unit_price, 2, '.', '') . '</ram:ChargeAmount>' . "\n";
            $xml .= '        </ram:NetPriceProductTradePrice>' . "\n";
            $xml .= '      </ram:SpecifiedLineTradeAgreement>' . "\n";
            $xml .= '      <ram:SpecifiedLineTradeDelivery>' . "\n";
            $xml .= '        <ram:BilledQuantity unitCode="C62">' . $quantity . '</ram:BilledQuantity>' . "\n";
            $xml .= '      </ram:SpecifiedLineTradeDelivery>' . "\n";
            $xml .= '      <ram:SpecifiedLineTradeSettlement>' . "\n";
            $xml .= '        <ram:SpecifiedTradeSettlementLineMonetarySummation>' . "\n";
            $xml .= '          <ram:LineTotalAmount>' . number_format($line_total, 2, '.', '') . '</ram:LineTotalAmount>' . "\n";
            $xml .= '        </ram:SpecifiedTradeSettlementLineMonetarySummation>' . "\n";
            if ($vat_rate > 0) {
                $xml .= '        <ram:ApplicableTradeTax>' . "\n";
                $xml .= '          <ram:TypeCode>VAT</ram:TypeCode>' . "\n";
                $xml .= '          <ram:CategoryCode>S</ram:CategoryCode>' . "\n";
                $xml .= '          <ram:RateApplicablePercent>' . number_format($vat_rate, 2, '.', '') . '</ram:RateApplicablePercent>' . "\n";
                $xml .= '          <ram:CalculatedAmount>' . number_format($line_vat, 2, '.', '') . '</ram:CalculatedAmount>' . "\n";
                $xml .= '          <ram:BasisAmount>' . number_format($line_total, 2, '.', '') . '</ram:BasisAmount>' . "\n";
                $xml .= '        </ram:ApplicableTradeTax>' . "\n";
            }
            $xml .= '      </ram:SpecifiedLineTradeSettlement>' . "\n";
            $xml .= '    </ram:IncludedSupplyChainTradeLineItem>' . "\n";
        }
        
        // ApplicableHeaderTradeAgreement
        $xml .= '    <ram:ApplicableHeaderTradeAgreement>' . "\n";
        
        // Vendeur
        $xml .= '      <ram:SellerTradeParty>' . "\n";
        $xml .= '        <ram:Name>' . htmlspecialchars($seller->company_name ?? '', ENT_XML1, 'UTF-8') . '</ram:Name>' . "\n";
        $xml .= '        <ram:PostalTradeAddress>' . "\n";
        $xml .= '          <ram:PostcodeCode>' . htmlspecialchars($seller->postal_code ?? '', ENT_XML1, 'UTF-8') . '</ram:PostcodeCode>' . "\n";
        $xml .= '          <ram:LineOne>' . htmlspecialchars($seller->address ?? '', ENT_XML1, 'UTF-8') . '</ram:LineOne>' . "\n";
        $xml .= '          <ram:CityName>' . htmlspecialchars($seller->city ?? '', ENT_XML1, 'UTF-8') . '</ram:CityName>' . "\n";
        $xml .= '          <ram:CountryID>' . $seller_country . '</ram:CountryID>' . "\n";
        $xml .= '        </ram:PostalTradeAddress>' . "\n";
        if (!empty($seller->tax_number)) {
            $xml .= '        <ram:SpecifiedTaxRegistration>' . "\n";
            $xml .= '          <ram:ID schemeID="VA">' . htmlspecialchars($seller->tax_number, ENT_XML1, 'UTF-8') . '</ram:ID>' . "\n";
            $xml .= '        </ram:SpecifiedTaxRegistration>' . "\n";
        }
        $xml .= '      </ram:SellerTradeParty>' . "\n";
        
        // Acheteur
        $xml .= '      <ram:BuyerTradeParty>' . "\n";
        $xml .= '        <ram:Name>' . htmlspecialchars($client->company_name ?? '', ENT_XML1, 'UTF-8') . '</ram:Name>' . "\n";
        $xml .= '        <ram:PostalTradeAddress>' . "\n";
        $xml .= '          <ram:PostcodeCode>' . htmlspecialchars($client->postal_code ?? '', ENT_XML1, 'UTF-8') . '</ram:PostcodeCode>' . "\n";
        $xml .= '          <ram:LineOne>' . htmlspecialchars($client->address ?? '', ENT_XML1, 'UTF-8') . '</ram:LineOne>' . "\n";
        $xml .= '          <ram:CityName>' . htmlspecialchars($client->city ?? '', ENT_XML1, 'UTF-8') . '</ram:CityName>' . "\n";
        $xml .= '          <ram:CountryID>' . $buyer_country . '</ram:CountryID>' . "\n";
        $xml .= '        </ram:PostalTradeAddress>' . "\n";
        if (!empty($client->tax_number)) {
            $xml .= '        <ram:SpecifiedTaxRegistration>' . "\n";
            $xml .= '          <ram:ID schemeID="VA">' . htmlspecialchars($client->tax_number, ENT_XML1, 'UTF-8') . '</ram:ID>' . "\n";
            $xml .= '        </ram:SpecifiedTaxRegistration>' . "\n";
        }
        $xml .= '      </ram:BuyerTradeParty>' . "\n";
        
        $xml .= '    </ram:ApplicableHeaderTradeAgreement>' . "\n";
        
        // ApplicableHeaderTradeSettlement
        $xml .= '    <ram:ApplicableHeaderTradeSettlement>' . "\n";
        $xml .= '      <ram:InvoiceCurrencyCode>' . $currency_code . '</ram:InvoiceCurrencyCode>' . "\n";
        
        // Totaux
        $xml .= '      <ram:SpecifiedTradeSettlementHeaderMonetarySummation>' . "\n";
        $xml .= '        <ram:LineTotalAmount>' . number_format($subtotal, 2, '.', '') . '</ram:LineTotalAmount>' . "\n";
        $xml .= '        <ram:TaxBasisTotalAmount>' . number_format($subtotal, 2, '.', '') . '</ram:TaxBasisTotalAmount>' . "\n";
        if ($total_vat > 0) {
            $xml .= '        <ram:TaxTotalAmount currencyID="' . $currency_code . '">' . number_format($total_vat, 2, '.', '') . '</ram:TaxTotalAmount>' . "\n";
        }
        $xml .= '        <ram:GrandTotalAmount>' . number_format($total_amount, 2, '.', '') . '</ram:GrandTotalAmount>' . "\n";
        $xml .= '        <ram:DuePayableAmount>' . number_format($total_amount, 2, '.', '') . '</ram:DuePayableAmount>' . "\n";
        $xml .= '      </ram:SpecifiedTradeSettlementHeaderMonetarySummation>' . "\n";
        
        // TVA globale
        if ($total_vat > 0) {
            foreach ($vat_breakdown as $rate => $breakdown) {
                $xml .= '      <ram:ApplicableTradeTax>' . "\n";
                $xml .= '        <ram:TypeCode>VAT</ram:TypeCode>' . "\n";
                $xml .= '        <ram:CategoryCode>S</ram:CategoryCode>' . "\n";
                $xml .= '        <ram:RateApplicablePercent>' . number_format($rate, 2, '.', '') . '</ram:RateApplicablePercent>' . "\n";
                $xml .= '        <ram:CalculatedAmount>' . number_format($breakdown['vat'], 2, '.', '') . '</ram:CalculatedAmount>' . "\n";
                $xml .= '        <ram:BasisAmount>' . number_format($breakdown['basis'], 2, '.', '') . '</ram:BasisAmount>' . "\n";
                $xml .= '      </ram:ApplicableTradeTax>' . "\n";
            }
        }
        
        $xml .= '    </ram:ApplicableHeaderTradeSettlement>' . "\n";
        $xml .= '  </rsm:SupplyChainTradeTransaction>' . "\n";
        $xml .= '</rsm:CrossIndustryInvoice>';
        
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
        
        if (strlen($country) === 2) {
            return strtoupper($country);
        }
        
        return 'FR';
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

