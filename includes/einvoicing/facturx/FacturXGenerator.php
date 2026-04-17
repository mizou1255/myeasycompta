<?php

namespace ECWP\EInvoicing\FacturX;

if (!defined('ABSPATH')) {
    exit;
}

use ECWP\EInvoicing\Model\InvoiceModel;
use Atgp\FacturX\Writer as FacturXWriter;
use Atgp\FacturX\Utils\ProfileHandler;

/**
 * Class FacturXGenerator
 * 
 * Génère le fichier XML Factur-X conforme EN16931.
 * 
 * @package ECWP\EInvoicing\FacturX
 */
class FacturXGenerator
{
    /**
     * Génère le XML Factur-X pour une facture donnée.
     * 
     * @param InvoiceModel $invoice
     * @return string Le contenu XML
     * @throws \Exception
     */
    public function generateXml(InvoiceModel $invoice): string
    {
        $invoice->validate();

        $seller = $invoice->getSeller();
        $buyer = $invoice->getBuyer();
        $lines = $invoice->getLines();
        $currency = $invoice->getCurrency();

        $lineTotal = 0.0;
        $taxTotal = 0.0;
        $grandTotal = 0.0;
        $taxByRate = [];

        foreach ($lines as $line) {
            $net = (float) ($line['total_price'] ?? 0);
            $gross = (float) ($line['total_amount'] ?? 0);
            $rate = (string) ((float) ($line['vat_rate'] ?? 0));
            $tax = $gross - $net;

            $lineTotal += $net;
            $taxTotal += $tax;
            $grandTotal += $gross;

            if (!isset($taxByRate[$rate])) {
                $taxByRate[$rate] = ['basis' => 0.0, 'tax' => 0.0];
            }
            $taxByRate[$rate]['basis'] += $net;
            $taxByRate[$rate]['tax'] += $tax;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElementNS('urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100', 'rsm:CrossIndustryInvoice');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:qdt', 'urn:un:unece:uncefact:data:standard:QualifiedDataType:100');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ram', 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:udt', 'urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $dom->appendChild($root);

        $context = $dom->createElement('rsm:ExchangedDocumentContext');
        $guideline = $dom->createElement('ram:GuidelineSpecifiedDocumentContextParameter');
        $guideline->appendChild($dom->createElement('ram:ID', 'urn:factur-x.eu:1p0:en16931'));
        $context->appendChild($guideline);
        $root->appendChild($context);

        $header = $dom->createElement('rsm:ExchangedDocument');
        $header->appendChild($dom->createElement('ram:ID', $invoice->getNumber()));
        $header->appendChild($dom->createElement('ram:TypeCode', $invoice->getTypeCode()));
        $issueDate = $dom->createElement('ram:IssueDateTime');
        $dateString = $dom->createElement('udt:DateTimeString', $invoice->getIssueDate()->format('Ymd'));
        $dateString->setAttribute('format', '102');
        $issueDate->appendChild($dateString);
        $header->appendChild($issueDate);
        $root->appendChild($header);

        $transaction = $dom->createElement('rsm:SupplyChainTradeTransaction');

        foreach ($lines as $index => $line) {
            $lineItem = $dom->createElement('ram:IncludedSupplyChainTradeLineItem');

            $lineDoc = $dom->createElement('ram:AssociatedDocumentLineDocument');
            $lineDoc->appendChild($dom->createElement('ram:LineID', (string) ($index + 1)));
            $lineItem->appendChild($lineDoc);

            $product = $dom->createElement('ram:SpecifiedTradeProduct');
            $product->appendChild($dom->createElement('ram:Name', (string) ($line['name'] ?? '')));
            $lineItem->appendChild($product);

            $lineAgreement = $dom->createElement('ram:SpecifiedLineTradeAgreement');
            $netPrice = $dom->createElement('ram:NetPriceProductTradePrice');
            $netPrice->appendChild($dom->createElement('ram:ChargeAmount', number_format((float) ($line['unit_price'] ?? 0), 2, '.', '')));
            $lineAgreement->appendChild($netPrice);
            $lineItem->appendChild($lineAgreement);

            $lineDelivery = $dom->createElement('ram:SpecifiedLineTradeDelivery');
            $billedQty = number_format((float) ($line['quantity'] ?? 0), 2, '.', '');
            $qty = $dom->createElement('ram:BilledQuantity', (string) $billedQty);
            $qty->setAttribute('unitCode', (string) ($line['unit_code'] ?? 'C62'));
            $lineDelivery->appendChild($qty);
            $lineItem->appendChild($lineDelivery);

            $lineSettlement = $dom->createElement('ram:SpecifiedLineTradeSettlement');
            $lineTax = $dom->createElement('ram:ApplicableTradeTax');
            $lineTax->appendChild($dom->createElement('ram:TypeCode', 'VAT'));
            $vRate = (float) ($line['vat_rate'] ?? 0);
            $catCode = ($vRate > 0) ? 'S' : 'E';
            $lineTax->appendChild($dom->createElement('ram:CategoryCode', $catCode));
            $lineTax->appendChild($dom->createElement('ram:RateApplicablePercent', number_format($vRate, 2, '.', '')));
            $lineSettlement->appendChild($lineTax);

            $lineSum = $dom->createElement('ram:SpecifiedTradeSettlementLineMonetarySummation');
            $lineSum->appendChild($dom->createElement('ram:LineTotalAmount', number_format((float) ($line['total_price'] ?? 0), 2, '.', '')));
            $lineSettlement->appendChild($lineSum);
            $lineItem->appendChild($lineSettlement);

            $transaction->appendChild($lineItem);
        }

        $agreement = $dom->createElement('ram:ApplicableHeaderTradeAgreement');

        // --- Vendeur (BT-27 à BT-34) ---
        $sellerParty = $dom->createElement('ram:SellerTradeParty');
        $sellerParty->appendChild($dom->createElement('ram:Name', (string) ($seller['name'] ?? '')));

        // SIREN/SIRET vendeur (BT-30)
        if (!empty($seller['siren'])) {
            $sellerOrg = $dom->createElement('ram:SpecifiedLegalOrganization');
            $sellerOrg->appendChild($dom->createElement('ram:ID', (string) $seller['siren']));
            $sellerParty->appendChild($sellerOrg);
        }

        // Numéro TVA intracommunautaire vendeur (BT-31)
        if (!empty($seller['vat_number'])) {
            $sellerTax = $dom->createElement('ram:SpecifiedTaxRegistration');
            $sellerTaxId = $dom->createElement('ram:ID', (string) $seller['vat_number']);
            $sellerTaxId->setAttribute('schemeID', 'VA');
            $sellerTax->appendChild($sellerTaxId);
            $sellerParty->appendChild($sellerTax);
        }

        $sellerAddress = $dom->createElement('ram:PostalTradeAddress');
        $sellerAddress->appendChild($dom->createElement('ram:PostcodeCode', (string) ($seller['address']['postal_code'] ?? '')));
        $sellerAddress->appendChild($dom->createElement('ram:LineOne',      (string) ($seller['address']['line1']       ?? '')));
        $sellerAddress->appendChild($dom->createElement('ram:CityName',     (string) ($seller['address']['city']        ?? '')));
        $sellerAddress->appendChild($dom->createElement('ram:CountryID',    (string) ($seller['address']['country']     ?? 'FR')));
        $sellerParty->appendChild($sellerAddress);
        $agreement->appendChild($sellerParty);

        // --- Acheteur (BT-44 à BT-49) ---
        $buyerParty = $dom->createElement('ram:BuyerTradeParty');
        $buyerParty->appendChild($dom->createElement('ram:Name', (string) ($buyer['name'] ?? '')));

        // SIREN/SIRET acheteur (BT-47) — obligatoire B2B France
        if (!empty($buyer['siren'])) {
            $buyerOrg = $dom->createElement('ram:SpecifiedLegalOrganization');
            $buyerOrg->appendChild($dom->createElement('ram:ID', (string) $buyer['siren']));
            $buyerParty->appendChild($buyerOrg);
        }

        // Numéro TVA intracommunautaire acheteur (BT-48)
        if (!empty($buyer['vat_number'])) {
            $buyerTax = $dom->createElement('ram:SpecifiedTaxRegistration');
            $buyerTaxId = $dom->createElement('ram:ID', (string) $buyer['vat_number']);
            $buyerTaxId->setAttribute('schemeID', 'VA');
            $buyerTax->appendChild($buyerTaxId);
            $buyerParty->appendChild($buyerTax);
        }

        $buyerAddress = $dom->createElement('ram:PostalTradeAddress');
        $buyerAddress->appendChild($dom->createElement('ram:PostcodeCode', (string) ($buyer['address']['postal_code'] ?? '')));
        $buyerAddress->appendChild($dom->createElement('ram:LineOne',      (string) ($buyer['address']['line1']       ?? '')));
        $buyerAddress->appendChild($dom->createElement('ram:CityName',     (string) ($buyer['address']['city']        ?? '')));
        $buyerAddress->appendChild($dom->createElement('ram:CountryID',    (string) ($buyer['address']['country']     ?? 'FR')));
        $buyerParty->appendChild($buyerAddress);
        $agreement->appendChild($buyerParty);

        $transaction->appendChild($agreement);

        $delivery = $dom->createElement('ram:ApplicableHeaderTradeDelivery');
        $transaction->appendChild($delivery);

        $settlement = $dom->createElement('ram:ApplicableHeaderTradeSettlement');
        $settlement->appendChild($dom->createElement('ram:InvoiceCurrencyCode', $currency));

        // Moyen de paiement (BG-16 / BT-81) — doit précéder ApplicableTradeTax dans le schéma CII
        $paymentMeans = $dom->createElement('ram:SpecifiedTradeSettlementPaymentMeans');
        $paymentMeansCode = $dom->createElement('ram:TypeCode', $invoice->getPaymentMeansCode());
        $paymentMeans->appendChild($paymentMeansCode);
        $settlement->appendChild($paymentMeans);

        foreach ($taxByRate as $rate => $amounts) {
            $tax = $dom->createElement('ram:ApplicableTradeTax');
            $tax->appendChild($dom->createElement('ram:CalculatedAmount', number_format((float) $amounts['tax'], 2, '.', '')));
            $tax->appendChild($dom->createElement('ram:TypeCode', 'VAT'));
            $tax->appendChild($dom->createElement('ram:BasisAmount', number_format((float) $amounts['basis'], 2, '.', '')));
            $vRate = (float) $rate;
            $catCode = ($vRate > 0) ? 'S' : 'E';
            $tax->appendChild($dom->createElement('ram:CategoryCode', $catCode));
            $tax->appendChild($dom->createElement('ram:RateApplicablePercent', number_format($vRate, 2, '.', '')));
            $settlement->appendChild($tax);
        }

        if ($invoice->getDueDate() instanceof \DateTime) {
            $terms    = $dom->createElement('ram:SpecifiedTradePaymentTerms');
            $dueDate  = $dom->createElement('ram:DueDateDateTime');
            $dueString = $dom->createElement('udt:DateTimeString', $invoice->getDueDate()->format('Ymd'));
            $dueString->setAttribute('format', '102');
            $dueDate->appendChild($dueString);
            $terms->appendChild($dueDate);
            $settlement->appendChild($terms);
        }

        $sum = $dom->createElement('ram:SpecifiedTradeSettlementHeaderMonetarySummation');
        $sum->appendChild($dom->createElement('ram:LineTotalAmount', number_format($lineTotal, 2, '.', '')));
        $sum->appendChild($dom->createElement('ram:TaxBasisTotalAmount', number_format($lineTotal, 2, '.', '')));
        $taxAmount = $dom->createElement('ram:TaxTotalAmount', number_format($taxTotal, 2, '.', ''));
        $taxAmount->setAttribute('currencyID', $currency);
        $sum->appendChild($taxAmount);
        $sum->appendChild($dom->createElement('ram:GrandTotalAmount', number_format($grandTotal, 2, '.', '')));
        $sum->appendChild($dom->createElement('ram:DuePayableAmount', number_format($grandTotal, 2, '.', '')));
        $settlement->appendChild($sum);

        $transaction->appendChild($settlement);
        $root->appendChild($transaction);

        return $dom->saveXML();
    }

    /**
     * Génère le PDF/A-3 avec le XML embarqué.
     * 
     * @param int $invoiceId
     * @return string Chemin du fichier PDF généré
     */
    public function getFacturXPdf(int $invoiceId): string
    {
        global $wpdb;

        $invoice = new InvoiceModel($invoiceId);
        $xmlContent = $this->generateXml($invoice);

        $profile = $wpdb->get_var($wpdb->prepare(
            "SELECT facturx_profile FROM " . ECWP_TABLE_INVOICES . " WHERE id = %d",
            $invoiceId
        ));
        $profile = $profile ?: ProfileHandler::PROFILE_FACTURX_EN16931;

        $pdfGenerator = new \ECWP\Admin\PDF\PDFGenerator($wpdb);
        $basePdfPath = $pdfGenerator->generateInvoicePDF($invoiceId, null, 'email');
        if (!is_string($basePdfPath) || !is_file($basePdfPath)) {
            throw new \Exception('Impossible de générer le PDF de base.');
        }

        $basePdf = file_get_contents($basePdfPath);
        if ($basePdf === false) {
            throw new \Exception('Impossible de lire le PDF de base.');
        }

        $writer = new FacturXWriter();
        $facturxPdf = $writer->generate($basePdf, $xmlContent, $profile, true);

        $dir = ECWP_PATH_DIR . 'uploads/facturx/';
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        global $wp_filesystem;
        if (!$wp_filesystem->is_dir($dir)) {
            $wp_filesystem->mkdir($dir);
        }

        $fileName = sanitize_file_name($invoice->getNumber() . '-facturx.pdf');
        $filePath = $dir . $fileName;

        $wp_filesystem->put_contents($filePath, $facturxPdf);

        $hash = hash('sha256', $facturxPdf);

        $wpdb->update(
            ECWP_TABLE_INVOICES,
            [
                'facturx_hash' => $hash,
                'facturx_pdf_path' => $filePath,
                'fiscal_status' => 'validated',
                'fiscal_status_updated_at' => current_time('mysql'),
                'facturx_profile' => $profile,
            ],
            ['id' => $invoiceId],
            ['%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );

        do_action('myeasycompta_invoice_facturx_ready', $invoiceId, $filePath);

        return $filePath;
    }
}
