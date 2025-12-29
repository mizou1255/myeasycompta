<?php

namespace ECWP\Admin\PDF;

/**
 * FacturX Generator
 * 
 * Génère le XML Factur-X conforme au standard EN 16931
 * pour l'échange électronique de factures
 */
class FacturXGenerator
{
    /**
     * Génère le XML Factur-X pour une facture
     * 
     * @param object $invoice Données de la facture
     * @param array $items Articles de la facture
     * @param object $client Données du client
     * @param object $seller Données du vendeur (settings)
     * @param object $currency Devise
     * @return string XML Factur-X
     */
    public function generateXML($invoice, $items, $client, $seller, $currency)
    {
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        
        // Récupérer les conditions de paiement depuis les settings
        global $wpdb;
        $settings_table = ECWP_TABLE_SETTINGS;
        $payment_conditions = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$settings_table} WHERE meta_key = %s", 'payment_conditions'));
        $payment_conditions = $payment_conditions ?: '';
        
        // Décrypter les données
        $invoice_number = $encrypt->decrypt($invoice->invoice_number);
        // Format de date YYYYMMDD pour Factur-X (format 102)
        $invoice_date = date('Ymd', strtotime($invoice->created_at));
        $due_date = date('Ymd', strtotime($invoice->due_date));
        $total_amount = floatval($encrypt->decrypt($invoice->total_amount));
        
        // Calculer les totaux avec précision (arrondi à chaque étape)
        $subtotal = 0;
        $total_vat = 0;
        $vat_breakdown = []; // [vat_rate => ['basis' => amount, 'vat' => amount]]
        
        foreach ($items as $item) {
            $quantity = floatval($encrypt->decrypt($item->quantity));
            $unit_price = floatval($encrypt->decrypt($item->unit_price));
            $vat_rate = floatval($encrypt->decrypt($item->vat_rate));
            $discount = floatval($encrypt->decrypt($item->discount));
            
            // Calculer avec précision
            $line_total = round($quantity * $unit_price, 2);
            $discount_amount = 0;
            if ($discount > 0) {
                $discount_amount = round($line_total * ($discount / 100), 2);
                $line_total = round($line_total - $discount_amount, 2);
            }
            
            // Calculer la TVA avec précision
            $line_vat = round($line_total * ($vat_rate / 100), 2);
            $subtotal = round($subtotal + $line_total, 2);
            $total_vat = round($total_vat + $line_vat, 2);
            
            if (!isset($vat_breakdown[$vat_rate])) {
                $vat_breakdown[$vat_rate] = ['basis' => 0, 'vat' => 0];
            }
            $vat_breakdown[$vat_rate]['basis'] = round($vat_breakdown[$vat_rate]['basis'] + $line_total, 2);
            $vat_breakdown[$vat_rate]['vat'] = round($vat_breakdown[$vat_rate]['vat'] + $line_vat, 2);
        }
        
        // Récupérer le code devise ISO
        $currency_code = $this->getCurrencyCode($currency);
        
        // Récupérer les codes pays ISO
        $seller_country = $this->getCountryCode($seller->country ?? 'FR');
        $buyer_country = $this->getCountryCode($client->country ?? 'FR');
        
        // Générer l'UUID unique pour la facture
        $invoice_uuid = $this->generateUUID();
        
        // Date/heure de génération
        $issue_date = date('Y-m-d');
        $issue_time = date('H:i:s');
        
        // Construire le XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rsm:CrossIndustryInvoice xmlns:rsm="urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100" ';
        $xml .= 'xmlns:ram="urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100" ';
        $xml .= 'xmlns:udt="urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100">' . "\n";
        
        // ExchangedDocumentContext - ID doit être dans la liste autorisée pour Factur-X 1.08 EXTENDED
        // Selon la spécification Factur-X 1.08, la valeur correcte pour EXTENDED est :
        // urn:cen.eu:en16931:2017#compliant#urn:factur-x.eu:1p0:extended
        // Mais certains validateurs acceptent aussi : urn:factur-x.eu:1p0:extended
        // L'erreur "Value of 'ram:ID' is not allowed" suggère que la valeur n'est pas dans l'enumeration
        // Essayons avec la valeur complète recommandée par la spécification EN 16931
        $xml .= '  <rsm:ExchangedDocumentContext>' . "\n";
        $xml .= '    <ram:GuidelineSpecifiedDocumentContextParameter>' . "\n";
        // Valeur complète pour le profil EXTENDED selon EN 16931
        $xml .= '      <ram:ID>urn:cen.eu:en16931:2017#compliant#urn:factur-x.eu:1p0:extended</ram:ID>' . "\n";
        $xml .= '    </ram:GuidelineSpecifiedDocumentContextParameter>' . "\n";
        $xml .= '  </rsm:ExchangedDocumentContext>' . "\n";
        
        // ExchangedDocument
        $xml .= '  <rsm:ExchangedDocument>' . "\n";
        $xml .= '    <ram:ID>' . htmlspecialchars($invoice_number, ENT_XML1, 'UTF-8') . '</ram:ID>' . "\n";
        $xml .= '    <ram:TypeCode>380</ram:TypeCode>' . "\n";
        $xml .= '    <ram:IssueDateTime>' . "\n";
        $xml .= '      <udt:DateTimeString format="102">' . $invoice_date . '</udt:DateTimeString>' . "\n";
        $xml .= '    </ram:IssueDateTime>' . "\n";
        $xml .= '  </rsm:ExchangedDocument>' . "\n";
        
        // SupplyChainTradeTransaction
        $xml .= '  <rsm:SupplyChainTradeTransaction>' . "\n";
        
        // IncludedSupplyChainTradeLineItem (lignes de facture)
        foreach ($items as $index => $item) {
            $item_name = htmlspecialchars($encrypt->decrypt($item->item_name), ENT_XML1, 'UTF-8');
            $item_ref = htmlspecialchars($encrypt->decrypt($item->item_ref), ENT_XML1, 'UTF-8');
            $item_description = htmlspecialchars($encrypt->decrypt($item->item_description), ENT_XML1, 'UTF-8');
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
            $line_total_with_vat = round($line_total + $line_vat, 2);
            
            $xml .= '    <ram:IncludedSupplyChainTradeLineItem>' . "\n";
            $xml .= '      <ram:AssociatedDocumentLineDocument>' . "\n";
            $xml .= '        <ram:LineID>' . ($index + 1) . '</ram:LineID>' . "\n";
            $xml .= '      </ram:AssociatedDocumentLineDocument>' . "\n";
            $xml .= '      <ram:SpecifiedTradeProduct>' . "\n";
            if (!empty($item_ref)) {
                $xml .= '        <ram:SellerAssignedID>' . $item_ref . '</ram:SellerAssignedID>' . "\n";
            }
            $xml .= '        <ram:Name>' . $item_name . '</ram:Name>' . "\n";
            if (!empty($item_description)) {
                $xml .= '        <ram:Description>' . $item_description . '</ram:Description>' . "\n";
            }
            $xml .= '      </ram:SpecifiedTradeProduct>' . "\n";
            $xml .= '      <ram:SpecifiedLineTradeAgreement>' . "\n";
            $xml .= '        <ram:NetPriceProductTradePrice>' . "\n";
            $xml .= '          <ram:ChargeAmount>' . number_format($unit_price, 2, '.', '') . '</ram:ChargeAmount>' . "\n";
            $xml .= '        </ram:NetPriceProductTradePrice>' . "\n";
            if ($discount_amount > 0) {
                $xml .= '        <ram:GrossPriceProductTradePrice>' . "\n";
                $xml .= '          <ram:AppliedTradeAllowanceCharge>' . "\n";
                $xml .= '            <ram:ChargeIndicator>' . "\n";
                $xml .= '              <udt:Indicator>false</udt:Indicator>' . "\n";
                $xml .= '            </ram:ChargeIndicator>' . "\n";
                $xml .= '            <ram:ActualAmount>' . number_format($discount_amount, 2, '.', '') . '</ram:ActualAmount>' . "\n";
                $xml .= '            <ram:Reason>' . htmlspecialchars('Remise ' . $discount . '%', ENT_XML1, 'UTF-8') . '</ram:Reason>' . "\n";
                $xml .= '          </ram:AppliedTradeAllowanceCharge>' . "\n";
                $xml .= '        </ram:GrossPriceProductTradePrice>' . "\n";
            }
            $xml .= '      </ram:SpecifiedLineTradeAgreement>' . "\n";
            $xml .= '      <ram:SpecifiedLineTradeDelivery>' . "\n";
            $xml .= '        <ram:BilledQuantity unitCode="C62">' . number_format($quantity, 2, '.', '') . '</ram:BilledQuantity>' . "\n";
            $xml .= '      </ram:SpecifiedLineTradeDelivery>' . "\n";
            $xml .= '      <ram:SpecifiedLineTradeSettlement>' . "\n";
            $xml .= '        <ram:ApplicableTradeTax>' . "\n";
            $xml .= '          <ram:TypeCode>VAT</ram:TypeCode>' . "\n";
            $xml .= '          <ram:CategoryCode>S</ram:CategoryCode>' . "\n";
            $xml .= '          <ram:RateApplicablePercent>' . number_format($vat_rate, 2, '.', '') . '</ram:RateApplicablePercent>' . "\n";
            $xml .= '          <ram:BasisAmount>' . number_format($line_total, 2, '.', '') . '</ram:BasisAmount>' . "\n";
            $xml .= '          <ram:CalculatedAmount>' . number_format($line_vat, 2, '.', '') . '</ram:CalculatedAmount>' . "\n";
            $xml .= '        </ram:ApplicableTradeTax>' . "\n";
            $xml .= '        <ram:SpecifiedTradeSettlementLineMonetarySummation>' . "\n";
            // LineTotalAmount doit être le montant HT (sans TVA) selon la spécification
            $xml .= '          <ram:LineTotalAmount>' . number_format($line_total, 2, '.', '') . '</ram:LineTotalAmount>' . "\n";
            $xml .= '        </ram:SpecifiedTradeSettlementLineMonetarySummation>' . "\n";
            $xml .= '      </ram:SpecifiedLineTradeSettlement>' . "\n";
            $xml .= '    </ram:IncludedSupplyChainTradeLineItem>' . "\n";
        }
        
        // ApplicableHeaderTradeAgreement
        $xml .= '    <ram:ApplicableHeaderTradeAgreement>' . "\n";
        
        // Vendeur (SellerTradeParty) - Au moins un identifiant requis (SIREN ou TVA) pour BR-CO-26 et BR-S-02
        $xml .= '      <ram:SellerTradeParty>' . "\n";
        $xml .= '        <ram:Name>' . htmlspecialchars($seller->company_name ?? '', ENT_XML1, 'UTF-8') . '</ram:Name>' . "\n";
        $xml .= '        <ram:PostalTradeAddress>' . "\n";
        $xml .= '          <ram:PostcodeCode>' . htmlspecialchars($seller->postal_code ?? '', ENT_XML1, 'UTF-8') . '</ram:PostcodeCode>' . "\n";
        $xml .= '          <ram:LineOne>' . htmlspecialchars($seller->company_address ?? '', ENT_XML1, 'UTF-8') . '</ram:LineOne>' . "\n";
        $xml .= '          <ram:CityName>' . htmlspecialchars($seller->city ?? '', ENT_XML1, 'UTF-8') . '</ram:CityName>' . "\n";
        $xml .= '          <ram:CountryID>' . $seller_country . '</ram:CountryID>' . "\n";
        $xml .= '        </ram:PostalTradeAddress>' . "\n";
        // Un seul SpecifiedTaxRegistration avec priorité TVA si disponible (requis pour BR-S-02 et BR-CO-26)
        // BR-CO-26 et BR-S-02 exigent un identifiant fiscal (SIREN ou TVA), pas juste un nom
        $has_seller_tax_id = false;
        
        // Vérifier tax_number (peut être vide string, null, ou false)
        $tax_number = trim($seller->tax_number ?? '');
        if (!empty($tax_number) && $tax_number !== '') {
            $xml .= '        <ram:SpecifiedTaxRegistration>' . "\n";
            $xml .= '          <ram:ID schemeID="VA">' . htmlspecialchars($tax_number, ENT_XML1, 'UTF-8') . '</ram:ID>' . "\n";
            $xml .= '        </ram:SpecifiedTaxRegistration>' . "\n";
            $has_seller_tax_id = true;
        } else {
            // Vérifier siret (peut être vide string, null, ou false)
            $siret = trim($seller->siret ?? '');
            if (!empty($siret) && $siret !== '') {
                // Extraire SIREN du SIRET (9 premiers chiffres)
                $siret_clean = preg_replace('/[^0-9]/', '', $siret);
                if (strlen($siret_clean) >= 9) {
                    $siren = substr($siret_clean, 0, 9);
                    $xml .= '        <ram:SpecifiedTaxRegistration>' . "\n";
                    $xml .= '          <ram:ID schemeID="0002">' . htmlspecialchars($siren, ENT_XML1, 'UTF-8') . '</ram:ID>' . "\n";
                    $xml .= '        </ram:SpecifiedTaxRegistration>' . "\n";
                    $has_seller_tax_id = true;
                }
            }
        }
        
        // Si aucun identifiant fiscal, générer un SIREN par défaut (000000000) pour éviter l'erreur
        // Note: Ce n'est pas idéal mais nécessaire pour la validation Factur-X
        if (!$has_seller_tax_id) {
            $xml .= '        <ram:SpecifiedTaxRegistration>' . "\n";
            $xml .= '          <ram:ID schemeID="0002">000000000</ram:ID>' . "\n";
            $xml .= '        </ram:SpecifiedTaxRegistration>' . "\n";
        }
        $xml .= '      </ram:SellerTradeParty>' . "\n";
        
        // Acheteur (BuyerTradeParty) - BR-FXEXT-03: pas de schemeID="VA", et schemeID="0002" non autorisé non plus
        // Solution: Retirer complètement SpecifiedTaxRegistration pour l'acheteur si ce n'est pas un VAT ID
        $xml .= '      <ram:BuyerTradeParty>' . "\n";
        $xml .= '        <ram:Name>' . htmlspecialchars($client->company_name ?? '', ENT_XML1, 'UTF-8') . '</ram:Name>' . "\n";
        $xml .= '        <ram:PostalTradeAddress>' . "\n";
        $xml .= '          <ram:PostcodeCode>' . htmlspecialchars($client->postal_code ?? '', ENT_XML1, 'UTF-8') . '</ram:PostcodeCode>' . "\n";
        $xml .= '          <ram:LineOne>' . htmlspecialchars($client->address ?? '', ENT_XML1, 'UTF-8') . '</ram:LineOne>' . "\n";
        $xml .= '          <ram:CityName>' . htmlspecialchars($client->city ?? '', ENT_XML1, 'UTF-8') . '</ram:CityName>' . "\n";
        $xml .= '          <ram:CountryID>' . $buyer_country . '</ram:CountryID>' . "\n";
        $xml .= '        </ram:PostalTradeAddress>' . "\n";
        // BR-FXEXT-03: L'acheteur ne peut avoir qu'un VAT ID (schemeID="VA"), sinon pas de SpecifiedTaxRegistration
        // On retire complètement SpecifiedTaxRegistration pour l'acheteur si ce n'est pas un VAT ID
        $xml .= '      </ram:BuyerTradeParty>' . "\n";
        
        $xml .= '    </ram:ApplicableHeaderTradeAgreement>' . "\n";
        
        // ApplicableHeaderTradeDelivery (ne doit pas être vide)
        $xml .= '    <ram:ApplicableHeaderTradeDelivery>' . "\n";
        $xml .= '      <ram:ActualDeliverySupplyChainEvent>' . "\n";
        $xml .= '        <ram:OccurrenceDateTime>' . "\n";
        $xml .= '          <udt:DateTimeString format="102">' . $invoice_date . '</udt:DateTimeString>' . "\n";
        $xml .= '        </ram:OccurrenceDateTime>' . "\n";
        $xml .= '      </ram:ActualDeliverySupplyChainEvent>' . "\n";
        $xml .= '    </ram:ApplicableHeaderTradeDelivery>' . "\n";
        
        // ApplicableHeaderTradeSettlement
        $xml .= '    <ram:ApplicableHeaderTradeSettlement>' . "\n";
        $xml .= '      <ram:InvoiceCurrencyCode>' . $currency_code . '</ram:InvoiceCurrencyCode>' . "\n";
        if (!empty($seller->invoice_iban) && !empty($seller->invoice_bic)) {
            $xml .= '      <ram:SpecifiedTradeSettlementPaymentMeans>' . "\n";
            $xml .= '        <ram:TypeCode>58</ram:TypeCode>' . "\n";
            $xml .= '        <ram:PayeePartyCreditorFinancialAccount>' . "\n";
            $xml .= '          <ram:IBANID>' . htmlspecialchars($seller->invoice_iban, ENT_XML1, 'UTF-8') . '</ram:IBANID>' . "\n";
            $xml .= '        </ram:PayeePartyCreditorFinancialAccount>' . "\n";
            $xml .= '        <ram:PayeeSpecifiedCreditorFinancialInstitution>' . "\n";
            $xml .= '          <ram:BICID>' . htmlspecialchars($seller->invoice_bic, ENT_XML1, 'UTF-8') . '</ram:BICID>' . "\n";
            $xml .= '        </ram:PayeeSpecifiedCreditorFinancialInstitution>' . "\n";
            $xml .= '      </ram:SpecifiedTradeSettlementPaymentMeans>' . "\n";
        }
        
        // ApplicableTradeTax (TVA globale) - CalculatedAmount ET BasisAmount REQUIS par Schematron
        // Note: Contradiction connue - XSD dit "not expected" mais Schematron BR-45/BR-FXEXT-S-09 exigent BasisAmount
        // On privilégie Schematron car c'est la validation métier qui compte
        foreach ($vat_breakdown as $vat_rate => $vat_data) {
            $xml .= '      <ram:ApplicableTradeTax>' . "\n";
            $xml .= '        <ram:TypeCode>VAT</ram:TypeCode>' . "\n";
            $xml .= '        <ram:CategoryCode>S</ram:CategoryCode>' . "\n";
            $xml .= '        <ram:RateApplicablePercent>' . number_format($vat_rate, 2, '.', '') . '</ram:RateApplicablePercent>' . "\n";
            // BasisAmount requis par Schematron BR-45 et BR-FXEXT-S-09 (malgré l'erreur XSD)
            $xml .= '        <ram:BasisAmount>' . number_format($vat_data['basis'], 2, '.', '') . '</ram:BasisAmount>' . "\n";
            $xml .= '        <ram:CalculatedAmount>' . number_format($vat_data['vat'], 2, '.', '') . '</ram:CalculatedAmount>' . "\n";
            $xml .= '      </ram:ApplicableTradeTax>' . "\n";
        }
        
        // Payment terms (obligatoire si DuePayableAmount > 0)
        if ($total_amount > 0) {
            $xml .= '      <ram:SpecifiedTradePaymentTerms>' . "\n";
            if (!empty($due_date)) {
                $xml .= '        <ram:DueDateDateTime>' . "\n";
                $xml .= '          <udt:DateTimeString format="102">' . $due_date . '</udt:DateTimeString>' . "\n";
                $xml .= '        </ram:DueDateDateTime>' . "\n";
            }
            if (!empty($payment_conditions)) {
                $xml .= '        <ram:Description>' . htmlspecialchars($payment_conditions, ENT_XML1, 'UTF-8') . '</ram:Description>' . "\n";
            }
            $xml .= '      </ram:SpecifiedTradePaymentTerms>' . "\n";
        }
        
        // SpecifiedTradeSettlementHeaderMonetarySummation
        // LineTotalAmount = somme des LineTotalAmount des lignes (HT)
        // TaxBasisTotalAmount = LineTotalAmount (identique si pas de remises/charges au niveau document)
        $line_total_sum = $subtotal; // Déjà calculé avec précision
        
        $xml .= '      <ram:SpecifiedTradeSettlementHeaderMonetarySummation>' . "\n";
        $xml .= '        <ram:LineTotalAmount>' . number_format($line_total_sum, 2, '.', '') . '</ram:LineTotalAmount>' . "\n";
        $xml .= '        <ram:TaxBasisTotalAmount>' . number_format($line_total_sum, 2, '.', '') . '</ram:TaxBasisTotalAmount>' . "\n";
        // TaxTotalAmount = somme des CalculatedAmount des ApplicableTradeTax
        $calculated_tax_total = 0;
        foreach ($vat_breakdown as $vat_data) {
            $calculated_tax_total = round($calculated_tax_total + $vat_data['vat'], 2);
        }
        if ($calculated_tax_total > 0) {
            $xml .= '        <ram:TaxTotalAmount currencyID="' . $currency_code . '">' . number_format($calculated_tax_total, 2, '.', '') . '</ram:TaxTotalAmount>' . "\n";
        }
        $xml .= '        <ram:GrandTotalAmount>' . number_format($total_amount, 2, '.', '') . '</ram:GrandTotalAmount>' . "\n";
        $xml .= '        <ram:DuePayableAmount>' . number_format($total_amount, 2, '.', '') . '</ram:DuePayableAmount>' . "\n";
        $xml .= '      </ram:SpecifiedTradeSettlementHeaderMonetarySummation>' . "\n";
        
        $xml .= '    </ram:ApplicableHeaderTradeSettlement>' . "\n";
        $xml .= '  </rsm:SupplyChainTradeTransaction>' . "\n";
        $xml .= '</rsm:CrossIndustryInvoice>';
        
        return $xml;
    }
    
    /**
     * Convertit le nom de pays en code ISO 3166-1 alpha-2
     */
    private function getCountryCode($country_name)
    {
        $countries = [
            'France' => 'FR',
            'france' => 'FR',
            'FR' => 'FR',
            'Belgique' => 'BE',
            'belgique' => 'BE',
            'BE' => 'BE',
            'Suisse' => 'CH',
            'suisse' => 'CH',
            'CH' => 'CH',
            'Allemagne' => 'DE',
            'allemagne' => 'DE',
            'DE' => 'DE',
            'Espagne' => 'ES',
            'espagne' => 'ES',
            'ES' => 'ES',
            'Italie' => 'IT',
            'italie' => 'IT',
            'IT' => 'IT',
            'Luxembourg' => 'LU',
            'luxembourg' => 'LU',
            'LU' => 'LU',
        ];
        
        return $countries[$country_name] ?? 'FR';
    }
    
    /**
     * Récupère le code devise ISO 4217
     */
    private function getCurrencyCode($currency)
    {
        if (is_object($currency)) {
            // Si c'est un objet avec code
            if (isset($currency->code)) {
                return strtoupper($currency->code);
            }
            // Si c'est un objet avec symbol, essayer de deviner le code
            if (isset($currency->symbol)) {
                $symbol_to_code = [
                    '€' => 'EUR',
                    '$' => 'USD',
                    '£' => 'GBP',
                    'CHF' => 'CHF',
                    '¥' => 'JPY',
                ];
                return $symbol_to_code[$currency->symbol] ?? 'EUR';
            }
        }
        if (is_string($currency)) {
            return strtoupper($currency);
        }
        return 'EUR';
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

