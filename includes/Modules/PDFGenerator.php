<?php

namespace ECWP\Admin\PDF;

require ECWP_PATH . '/vendor/autoload.php';

class PDFGenerator
{
    private $wpdb;
    private $settings_array = [];
    private $credit_color;
    private $invoice_color;
    private $quote_color;
    private $logo_mentions_active;
    private $logo_path;
    private $logo_width;
    private $logo_mentions;
    private $company_name;
    private $company_address;
    private $postal_code;
    private $city;
    private $country;
    private $company_phone;
    private $fax;
    private $siret;
    private $tax_number;
    private $show_phone;
    private $show_email;
    private $show_siren;
    private $show_tax_number;
    private $show_watermark;
    private $show_watermark_only_paid;
    private $payment_conditions;
    private $payment_mode;
    private $invoice_iban;
    private $invoice_bic;
    private $invoice_terms;
    private $invoice_footer;
    private $credit_terms;
    private $credit_footer;
    private $quote_terms;
    private $quote_footer;
    private $date_format;
    private $vat_active;
    private $default_vat;
    private $currency_position;
    private $vat_rate = 0;
    private $signature_active = 0;
    private $active_disbursements;

    /**
     * @param mixed $wpdb
     */
    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
        $this->initializeSettings();
    }

    /**
     * @return [type]
     */
    private function initializeSettings()
    {
        global $wpdb;
        $settings = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i", ECWP_TABLE_SETTINGS));

        foreach ($settings as $setting) {
            $this->settings_array[$setting->meta_key] = $setting->meta_value;
        }
        $this->logo_path = $this->settings_array['logo_path'] ?? '';
        $this->logo_width = $this->settings_array['logo_width'] ?? '';
        $this->logo_mentions = $this->settings_array['logo_mentions'] ?? '';
        $this->invoice_color = $this->settings_array['invoice_color'] ?? '#ff6a00';
        $this->credit_color = $this->settings_array['credit_color'] ?? '#ff6a00';
        $this->quote_color = $this->settings_array['quote_color'] ?? '#ff6a00';
        $this->company_name = $this->settings_array['company_name'] ?? '';
        $this->company_address = $this->settings_array['company_address'] ?? '';
        $this->postal_code = $this->settings_array['postal_code'] ?? '';
        $this->city = $this->settings_array['city'] ?? '';
        $this->country = $this->settings_array['country'] ?? '';
        $this->company_phone = $this->settings_array['company_phone'] ?? '';
        $this->fax = $this->settings_array['fax'] ?? '';
        $this->siret = $this->settings_array['company_code'] ?? '';
        $this->tax_number = $this->settings_array['tax_number'] ?? '';
        $this->show_phone = $this->settings_array['show_phone'] ?? '1';
        $this->show_email = $this->settings_array['show_email'] ?? '1';
        $this->show_siren = $this->settings_array['show_siren'] ?? '1';
        $this->show_tax_number = $this->settings_array['show_tax_number'] ?? '1';
        $this->show_watermark = $this->settings_array['show_watermark'] ?? '1';
        $this->show_watermark_only_paid = $this->settings_array['show_watermark_only_paid'] ?? '1';
        $this->payment_conditions = $this->settings_array['payment_conditions'] ?? '';
        $this->payment_mode = $this->settings_array['payment_mode'] ?? '';
        $this->invoice_iban = $this->settings_array['invoice_iban'] ?? '';
        $this->invoice_bic = $this->settings_array['invoice_bic'] ?? '';
        $this->invoice_terms = $this->settings_array['invoice_terms'] ?? '';
        $this->invoice_footer = $this->settings_array['invoice_footer'] ?? '';
        $this->credit_terms = $this->settings_array['credit_terms'] ?? '';
        $this->credit_footer = $this->settings_array['credit_footer'] ?? '';
        $this->quote_terms = $this->settings_array['quote_terms'] ?? '';
        $this->quote_footer = $this->settings_array['quote_footer'] ?? '';
        $this->date_format = $this->convertFormatDate($this->settings_array['date_format']) ?? 'd-m-Y';
        $this->logo_mentions_active = $this->settings_array['logo_mentions_active'] ?? '1';
        $this->vat_active = $this->settings_array['vat_active'] ?? '1';
        $this->default_vat = $this->settings_array['default_vat'] ?? '1';
        $this->currency_position = $this->settings_array['currency_position'] ?? 'after';
        $this->signature_active = $this->settings_array['easy_compta_signature_addon_active'] ?? 0;
        $this->active_disbursements = $this->settings_array['active_disbursements'] ?? 0; // version 1.4.0
    }

    /**
     * @param mixed $date
     *
     * @return [type]
     */
    private function convertFormatDate($date)
    {
        $replacements = [
            'DD' => 'd',
            'ddd' => 'D',
            'D' => 'j',
            'dddd' => 'l',
            'E' => 'N',
            'o' => 'S',
            'e' => 'w',
            'DDD' => 'z',
            'W' => 'W',
            'MMMM' => 'F',
            'MM' => 'm',
            'MMM' => 'M',
            'YYYY' => 'Y',
            'M' => 'n',
            'h' => 'g',
            'H' => 'G',
            'hh' => 'h',
            'HH' => 'H',
            'mm' => 'i',
            'ss' => 's',
            'SSS' => 'u',
            'zz' => 'e',
            'A' => 'a',
            'a' => 'A',
            'X' => 'U',
        ];

        return strtr($date, $replacements);
    }

    /**
     * @param mixed $invoice_id
     *
     * @return [type]
     */
    public function generateInvoicePDF($invoice_id, $currency_id, $type = 'show')
    {
        global $wpdb;
        $invoice = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", ECWP_TABLE_INVOICES, $invoice_id));
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                ie.id,
                ie.item_name,
                ie.item_ref,
                ie.item_category,
                ac.name as category_name,
                ie.item_description,
                ie.quantity,
                ie.vat_rate,
                ie.unit_price,
                ie.discount,
                ie.total_price,
                ie.total_amount,
                ie.item_order
            FROM
                %i ie
            LEFT JOIN
                %i ac
            ON
                ie.item_category = ac.id
            WHERE
                ie.invoice_id = %d
            ORDER BY
                ie.item_order ASC",
                ECWP_TABLE_INVOICE_ELEMENTS, ECWP_TABLE_ARTICLES_CATEGORIES,
                $invoice_id),
            OBJECT
        );
        $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", ECWP_TABLE_CLIENTS, $invoice->client_id));

        if ($currency_id) {
            $default_currency_symbol = $this->getDefaultCurrencySymbol($currency_id);
        } else {
            $default_currency_symbol = $this->getDefaultCurrencySymbol($client->currency_id);
        }

        if ($currency_id != null && $currency_id !== $client->currency_id) {
            $exchange_rate = $invoice->exchange_rate;
            foreach ($items as &$item) {
                $item->unit_price = $item->unit_price * $exchange_rate;
            }
        }

        $html = $this->generateHTML('invoice', $invoice, $items, $client, $default_currency_symbol);
        $mpdf = new \Mpdf\Mpdf([
            'margin_left' => 20,
            'margin_right' => 15,
            'margin_top' => 10,
            'margin_bottom' => 25,
            'margin_header' => 10,
            'margin_footer' => 10,
        ]);

        $mpdf->SetTitle(htmlspecialchars($invoice->invoice_number));
        $mpdf->SetAuthor(htmlspecialchars($client->company_name));

        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        $invoice_number = $encrypt->decrypt($invoice->invoice_number);
        $invoice_status = $encrypt->decrypt($invoice->status);
        if ($this->show_watermark == 1) {
            if ($this->show_watermark_only_paid == 1) {
                if ($invoice_status == 'paid') {
                    $mpdf->SetWatermarkText(__('PAID', 'my-easy-compta'), 0.1);
                    $mpdf->showWatermarkText = true;
                    $mpdf->watermarkTextAlpha = 0.1;
                }
            } else {
                if ($invoice_status == 'draft') {
                    $invoice_status = __('DRAFT', 'my-easy-compta');
                } else if ($invoice_status == 'unpaid') {
                    $invoice_status = __('UNPAID', 'my-easy-compta');
                }
                $mpdf->SetWatermarkText($invoice_status, 0.1);
                $mpdf->showWatermarkText = true;
                $mpdf->watermarkTextAlpha = 0.1;
            }
        }

        $mpdf->WriteHTML($html);

        if ($type == 'email') {
            $pdf_dir = ECWP_PATH_DIR . 'uploads/pdfs/';

            if (!function_exists('WP_Filesystem')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();
            }
            global $wp_filesystem;
            if (!$wp_filesystem->is_dir($pdf_dir)) {
                $wp_filesystem->mkdir($pdf_dir);
            }
            $pdf_file_path = $pdf_dir . '/' . $invoice_number . '.pdf';
            $mpdf->Output($pdf_file_path, \Mpdf\Output\Destination::FILE);
            return $pdf_file_path;
        } else {
            $mpdf->Output($invoice_number . '.pdf', 'I');
        }
    }

    public function generateCreditPDF($credit_id, $currency_id)
    {
        global $wpdb;
        $credits = $wpdb->get_row($wpdb->prepare("SELECT c.*, i.id, i.invoice_number, i.client_id, i.total_amount, i.due_date
                                            FROM %i c
                                            LEFT JOIN %i i ON c.invoice_id = i.id
                                            WHERE c.id = %d",
            ECWP_TABLE_CREDITS,
            ECWP_TABLE_INVOICES,
            $credit_id));

        if (!$credits) {
            return new \WP_Error('credit_not_found', __('Credit not found', 'my-easy-compta'), array('status' => 404));
        }
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                ie.id,
                ie.item_name,
                ie.item_ref,
                ie.item_category,
                ac.name AS category_name,
                ie.item_description,
                ie.quantity,
                ie.vat_rate,
                ie.unit_price,
                ie.discount,
                ie.total_price,
                ie.total_amount,
                ie.item_order,
                credits.credit_number,
                credits.created_at
             FROM
                %i ie
             LEFT JOIN
                %i ac ON ie.item_category = ac.id
             LEFT JOIN
                %i credits ON ie.invoice_id = credits.invoice_id
             WHERE
                ie.invoice_id = %d
             ORDER BY
                ie.item_order ASC",
                ECWP_TABLE_INVOICE_ELEMENTS, ECWP_TABLE_ARTICLES_CATEGORIES, ECWP_TABLE_CREDITS,
                $credits->invoice_id
            ),
            OBJECT
        );

        $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", ECWP_TABLE_CLIENTS, $credits->client_id));

        if ($currency_id) {
            $default_currency_symbol = $this->getDefaultCurrencySymbol($currency_id);
        } else {
            $default_currency_symbol = $this->getDefaultCurrencySymbol($client->currency_id);
        }
        if ($currency_id != null && $currency_id !== $client->currency_id) {
            $exchange_rate = $credits->exchange_rate;
            foreach ($items as &$item) {
                $item->unit_price = $item->unit_price * $exchange_rate;
            }
        }

        $html = $this->generateHTML('credit_invoice', $credits, $items, $client, $default_currency_symbol);
        $mpdf = new \Mpdf\Mpdf([
            'margin_left' => 20,
            'margin_right' => 15,
            'margin_top' => 10,
            'margin_bottom' => 25,
            'margin_header' => 10,
            'margin_footer' => 10,
        ]);

        $mpdf->SetTitle(htmlspecialchars($credits->invoice_number . ' - Credit Note'));
        $mpdf->SetAuthor(htmlspecialchars($client->company_name));
        $mpdf->WriteHTML($html);

        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        $invoice_number = $encrypt->decrypt($credits->invoice_number);

        $mpdf->Output($invoice_number . '_credit.pdf', 'D');
    }

/**
 * @param mixed $quote_id
 *
 * @return [type]
 */
    public function generateQuotePDF($quote_id, $type = 'show')
    {
        global $wpdb;
        $quote = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", ECWP_TABLE_QUOTES, $quote_id));
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
ie.id,
ie.item_name,
ie.item_ref,
ie.item_category,
ac.name as category_name,
ie.item_description,
ie.quantity,
ie.vat_rate,
ie.unit_price,
ie.discount,
ie.total_price,
ie.total_amount,
ie.item_order
FROM
%i ie
LEFT JOIN
%i ac
ON
ie.item_category = ac.id
WHERE
ie.quote_id = %d
ORDER BY
ie.item_order ASC",
                ECWP_TABLE_QUOTE_ELEMENTS, ECWP_TABLE_ARTICLES_CATEGORIES,
                $quote_id),
            OBJECT
        );
        $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", ECWP_TABLE_CLIENTS, $quote->client_id));
        $default_currency_symbol = $this->getDefaultCurrencySymbol($client->currency_id);

        $html = $this->generateHTML('quote', $quote, $items, $client, $default_currency_symbol);

        $mpdf = new \Mpdf\Mpdf([
            'margin_left' => 20,
            'margin_right' => 15,
            'margin_top' => 10,
            'margin_bottom' => 25,
            'margin_header' => 10,
            'margin_footer' => 10,
        ]);

        $mpdf->SetTitle(htmlspecialchars($quote->quote_number));
        $mpdf->SetAuthor(htmlspecialchars($client->company_name));

        $mpdf->WriteHTML($html);
        if ($type == 'email') {
            $pdf_dir = ECWP_PATH_DIR . 'uploads/pdfs/';

            if (!function_exists('WP_Filesystem')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();
            }
            global $wp_filesystem;
            if (!$wp_filesystem->is_dir($pdf_dir)) {
                $wp_filesystem->mkdir($pdf_dir);
            }
            $pdf_file_path = $pdf_dir . '/' . $quote->quote_number . '.pdf';
            $mpdf->Output($pdf_file_path, \Mpdf\Output\Destination::FILE);
            return $pdf_file_path;
        } else {
            $mpdf->Output($quote->quote_number . '.pdf', 'I');
        }
    }

/**
 * @param mixed $client_currency_id
 *
 * @return [type]
 */
    private function getDefaultCurrencySymbol($client_currency_id)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT symbol FROM %i WHERE id = %d", ECWP_TABLE_CURRENCY, $client_currency_id));
    }

/**
 * @param mixed $type
 * @param mixed $data
 * @param mixed $items
 * @param mixed $client
 * @param mixed $default_currency_symbol
 *
 * @return [type]
 */
    private function generateHTML($type, $data, $items, $client, $default_currency_symbol)
    {
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        $sub_total = 0;
        $sub_total_discounted = 0;
        $sub_total_discounted_with_vat = 0;
        $show_type = __('Quote', 'my-easy-compta');
        $payment_type = __('Due date', 'my-easy-compta');
        $date_show_type = __('Date planned', 'my-easy-compta');
        $date_type = "";
        $invoice_status = "";
        $invoice_pyament_conditions = "";
        $invoice_pyament_mode = "";
        $invoice_iban = "";
        $invoice_bic = "";
        $discount_exist = false;
        if ($type == 'invoice') {
            $number = $encrypt->decrypt($data->invoice_number);
            $invoice_status = $encrypt->decrypt($data->status);
            $global_color = $this->invoice_color;
            $show_type = __('Invoice', 'my-easy-compta');
            $payment_type = __('Payment date', 'my-easy-compta');
            $date_show_type = __('Created at', 'my-easy-compta');
            $date_type = $this->formatDate($data->created_at);
            $terms = $this->invoice_terms;
            $invoice_pyament_conditions = $this->payment_conditions;
            $invoice_pyament_mode = $this->payment_mode;
            $invoice_iban = $this->invoice_iban;
            $invoice_bic = $this->invoice_bic;
            $footer = $this->invoice_footer;

            if (isset($data->shipping_amount)) {
                $shipping_fees = $encrypt->decrypt($data->shipping_amount);
            }

        } else if ($type == 'credit_invoice') {
            $show_type = __('Credit', 'my-easy-compta');
            $number = $data->credit_number;
            $global_color = $this->credit_color;
            $payment_type = __('Payment date', 'my-easy-compta');
            $date_show_type = __('Created at', 'my-easy-compta');
            $date_type = $this->formatDate($data->created_at);
            $terms = $this->credit_terms;
            $footer = $this->credit_footer;
        } else {
            $number = $data->quote_number;
            $global_color = $this->quote_color;
            $date_type = $this->formatDate($data->provisional_start_date);
            $terms = $this->quote_terms;
            $footer = $this->quote_footer;
        }

        $html = '<html>

<body>
    <htmlpagefooter name="myfooter">
        <div
            style="font-size: 8pt; text-align: center; padding-top: 3mm; width:100%;font-family: dejavusanscondensed;font-size: 9pt;line-height: 13pt;color: #777777;">
            ' . $footer . '
        </div>
    </htmlpagefooter>

    <sethtmlpagefooter name="myfooter" value="on" />
    <div>
        <table width="100%" style="font-family: dejavusanscondensed;font-size: 10pt;line-height: 13pt;color: #777777;">
            <tr>
                <td width="60%" height="100">
                    <img style="width: ' . $this->logo_width . 'px;" src="' . $this->logo_path . '" /><br /><br />';

        if ($this->logo_mentions_active == 1) {
            $html .= '<p style="margin: 4pt 0 0 0;">' . $this->logo_mentions . '</p>';
        }
        $html .= '</td>
                <td width="40%" style="text-align: right;">
                    <div style="font-weight: bold; color: #111111; font-size: 20pt; text-transform: uppercase;">' .
        $show_type . '</div>
                    <table>
                        <tr>
                            <td width="10%">&nbsp;</td>
                            <td width="55%"
                                style="color: ' . $global_color . '; text-align: left; font-size: 9pt; text-transform: uppercase;">
                                ' . __('Reference No', 'my-easy-compta') . ':</td>
                            <td width="25%" style="text-align: right; font-size: 9pt;">' . $number . '</td>
                        </tr>
                        <tr>
                            <td width="10%">&nbsp;</td>
                            <td width="55%"
                                style="color: ' . $global_color . '; text-align: left; font-size: 9pt; text-transform: uppercase;">
                                ' . $date_show_type . ' :</td>
                            <td width="25%" style="text-align: right; font-size: 9pt;">' . $date_type . '</td>
                        </tr>
                        <tr>
                            <td width="10%">&nbsp;</td>
                            <td width="55%"
                                style="color: ' . $global_color . '; text-align: left; font-size: 9pt; text-transform: uppercase;">
                                ' . $payment_type . ' :</td>
                            <td width="25%" style="text-align: right; font-size: 9pt;">' .
        $this->formatDate($data->due_date) . '</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-bottom: 20px; margin-top: 30px;">
        <table width="100%" cellpadding="10"
            style="vertical-align: top; font-family: dejavusanscondensed;font-size: 10pt;line-height: 13pt;color: #777777;">
            <tr>
                <td width="45%"
                    style="border-bottom:0.2mm solid ' . $global_color . '; font-size: 9pt; font-weight:bold; color: ' . $global_color . '; text-transform: uppercase;">
                    ' . __('Received From', 'my-easy-compta') . '</td>
                <td width="10%">&nbsp;</td>
                <td width="45%"
                    style="border-bottom:0.2mm solid ' . $global_color . '; font-size: 9pt; font-weight:bold; color: ' . $global_color . '; text-transform: uppercase;">
                    ' . __('Recipient', 'my-easy-compta') . '</td>
            </tr>
            <tr>
                <td width="45%">
                    <span style="font-size: 11pt; font-weight: bold; color: #111111;">' . $this->company_name .
        '</span><br />
                    ' . $this->company_address . '<br>
                    ' . $this->postal_code . ', ' . $this->city . '<br>
                    ' . $this->country . '<br>';

        if ($this->company_phone) {
            $html .= '<b>' . __('Phone', 'my-easy-compta') . ' : </b>' . $this->company_phone . '<br>';
        }
        if ($this->fax) {
            $html .= '<b>Fax : </b>' . $this->fax . '<br>';
        }
        if ($this->siret) {
            $html .= '<b>' . __('SIRET n°', 'my-easy-compta') . ' : </b>' . $this->siret . '<br>';
        }
        if ($this->tax_number) {
            $html .= '<b>' . __('Tax number', 'my-easy-compta') . ' : </b>' . $this->tax_number . '<br>';
        }
        $html .= '
                </td>
                <td width="10%">&nbsp;</td>
                <td width="45%">
                    <span style="font-size: 11pt; font-weight: bold; color: #111111;">' . $client->company_name .
        '</span><br />
                    ' . $client->address . '<br>
                    ' . $client->postal_code . ', ' . $client->city . '<br>
                    ' . $client->country . '<br>';

        if ($client->phone && $this->show_phone == 1) {
            $html .= '<b>' . __('Phone', 'my-easy-compta') . ' : </b>' . $client->phone . '<br>';
        }
        if ($client->email && $this->show_email == 1) {
            $html .= '<b>' . __('Email', 'my-easy-compta') . ' : </b>' . $client->email . '<br>';
        }
        if ($client->siren_number && $this->show_siren == 1) {
            $html .= '<b>' . __('SIREN n°', 'my-easy-compta') . ' : </b>' . $client->siren_number . '<br>';
        }
        if ($client->tax_number && $this->show_tax_number == 1) {
            $html .= '<b>' . __('Tax number', 'my-easy-compta') . ' : </b>' . $client->tax_number . '<br>';
        }

        $items_html = "";
        $tva_totaux = [];
        foreach ($items as $item) {
            if ($type == 'invoice' || $type == 'credit_invoice') {
                $discount_percentage = intval($encrypt->decrypt($item->discount));
                if ($discount_percentage > 0) {
                    $discount_exist = true;
                }
            } else {
                $discount_percentage = intval($item->discount);
                if ($discount_percentage > 0) {
                    $discount_exist = true;
                }
            }
        }
        foreach ($items as $item) {
            if ($type == 'invoice' || $type == 'credit_invoice') {
                $quantity = $encrypt->decrypt($item->quantity);
                $unit_price = floatval($encrypt->decrypt($item->unit_price));
                $discount_percentage = intval($encrypt->decrypt($item->discount));
                $vat_rate = intval($encrypt->decrypt($item->vat_rate));
                $item_ref = $encrypt->decrypt($item->item_ref);
                $item_name = $encrypt->decrypt($item->item_name);
                $item_description = $encrypt->decrypt($item->item_description);
            } else {
                $quantity = $item->quantity;
                $unit_price = floatval($item->unit_price);
                $discount_percentage = intval($item->discount);
                $vat_rate = intval($item->vat_rate);
                $item_ref = $item->item_ref;
                $item_name = $item->item_name;
                $item_description = $item->item_description;
            }

            $item_category = $item->category_name;
            $item_total = $quantity * $unit_price;

            if ($this->vat_active == 1) {
                $discount_amount = ($item_total * $discount_percentage) / 100;
                $item_total_after_discount = $item_total - $discount_amount;
                $item_total_vat = ($item_total_after_discount * $vat_rate) / 100;
                $total_after_discount_with_vat = $item_total_after_discount + $item_total_vat;

                $sub_total_discounted_with_vat += $total_after_discount_with_vat;

                if (!isset($tva_totaux[$vat_rate])) {
                    $tva_totaux[$vat_rate] = 0;
                }
                $tva_totaux[$vat_rate] += $item_total_vat;
            } else {
                $discount_amount_with_vat = ($item_total * $discount_percentage) / 100;
                $total_after_discount_with_vat = $item_total - $discount_amount_with_vat;
            }

            $discount_amount = ($item_total * $discount_percentage) / 100;
            $total_after_discount = $item_total - $discount_amount;
            $sub_total += $item_total;
            $sub_total_discounted += $total_after_discount;

            $items_html .= '<tr>
                <td width="10%" style="border: 0.2mm solid #ffffff; background-color: #F5F5F5; vertical-align: top;">' .
            nl2br($item_ref) . '</td>
                <td width="45%"
                    style="text-align: left; border: 0.2mm solid #ffffff; background-color: #F5F5F5; vertical-align: top;">
                    <div
                        style="margin-bottom:6px; color: #4e6179; vertical-align: middle; padding: 5px 10px; background: #e3e9f4; font-size:10px">
                        ' . nl2br($item_category) . '</div>
                    <div style="margin-bottom:6px; font-weight:bold; color: #111111; vertical-align: top;">' .
            nl2br($item_name) . '</div>
                    ' . nl2br($item_description) . '
                </td>
                <td width="10%"
                    style="text-align: center;border: 0.2mm solid #ffffff; background-color: #F5F5F5; vertical-align: top;">
                    ' . $quantity . '</td>
                <td width="15%"
                    style="text-align: right;border: 0.2mm solid #ffffff; background-color: #F5F5F5; vertical-align: top;">
                    ' . $this->positionCurrency($this->formatAmount($unit_price), $default_currency_symbol->symbol) . '
                </td>';
            if ($this->vat_active == 1) {
                $items_html .= '<td width="15%"
                    style="text-align: right;border: 0.2mm solid #ffffff; background-color: #F5F5F5; vertical-align: top;">
                    ' . $this->positionCurrency($this->formatAmount($item_total_vat), $default_currency_symbol->symbol)
                    . '<br /><small>' . $vat_rate . '%</small></td>';
            }
            if ($discount_exist) {
                $items_html .= '<td width="15%"
                    style="text-align: right;border: 0.2mm solid #ffffff; background-color: #F5F5F5; vertical-align: top;">
                    ' . $this->positionCurrency($this->formatAmount($discount_amount), $default_currency_symbol->symbol) . '<br /><small>' . $discount_percentage . '%</small></td>';
            }
            $items_html .= '<td width="15%"
                    style="text-align: right;border: 0.2mm solid #ffffff; background-color: #F5F5F5; vertical-align: top;">
                    ' . $this->positionCurrency($this->formatAmount($total_after_discount_with_vat),
                $default_currency_symbol->symbol) . '</td>
            </tr>';
        }

        $html .= '
                </td>
            </tr>
        </table>
    </div>

    <table class="items" width="100%"
        style=" font-family: dejavusanscondensed;line-height: 13pt;border-spacing:3px; font-size: 9pt; border-collapse: collapse;"
        cellpadding="10">
        <thead>
            <tr>
                <td width="10%"
                    style="vertical-align: bottom; text-align: center; text-transform: uppercase; font-size: 7pt; font-weight: bold; background-color: #FFFFFF; color: #111111;border-bottom: 0.2mm solid ' . $global_color . '">
                    ' . __('Ref', 'my-easy-compta') . '</td>
                <td width="45%"
                    style="vertical-align: bottom; text-align: left; text-transform: uppercase; font-size: 7pt; font-weight: bold; background-color: #FFFFFF; color: #111111;border-bottom: 0.2mm solid ' . $global_color . '">
                    ' . __('Item name', 'my-easy-compta') . '</td>
                <td width="10%"
                    style="vertical-align: bottom; text-align: center; text-transform: uppercase; font-size: 7pt; font-weight: bold; background-color: #FFFFFF; color: #111111;border-bottom: 0.2mm solid ' . $global_color . '">
                    ' . __('Qty', 'my-easy-compta') . '</td>
                <td width="15%"
                    style="vertical-align: bottom; text-align: center; text-transform: uppercase; font-size: 7pt; font-weight: bold; background-color: #FFFFFF; color: #111111;border-bottom: 0.2mm solid ' . $global_color . '">
                    ' . __('Unit price', 'my-easy-compta') . '</td>';

        if ($this->vat_active == 1) {
            $html .= '<td width="15%"
                    style="vertical-align: bottom; text-align: center; text-transform: uppercase; font-size: 7pt; font-weight: bold; background-color: #FFFFFF; color: #111111;border-bottom: 0.2mm solid ' . $global_color . '">
                    ' . __('Vat', 'my-easy-compta') . '</td>';
        }
        if ($discount_exist) {
            $html .= '<td width="15%"
                    style="vertical-align: bottom; text-align: center; text-transform: uppercase; font-size: 7pt; font-weight: bold; background-color: #FFFFFF; color: #111111;border-bottom: 0.2mm solid ' . $global_color . '">
                    ' . __('Discount', 'my-easy-compta') . '</td>';
        }
        $html .= '<td width="15%"
                    style="vertical-align: bottom; text-align: center; text-transform: uppercase; font-size: 7pt; font-weight: bold; background-color: #FFFFFF; color: #111111;border-bottom: 0.2mm solid ' . $global_color . '">
                    ' . __('Total', 'my-easy-compta') . '</td>
            </tr>
        </thead>
        <tbody>';

        if ($this->vat_active == 1) {
            $balance_due = $sub_total_discounted_with_vat;
        } else {
            $balance_due = $sub_total_discounted;
        }

        if ($this->active_disbursements == 1) {
            global $wpdb;
            $disbursements = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM %i WHERE invoice_id = %d", ECWP_TABLE_DISBURSEMENTS, $data->id)
            );

            $disbursement_total = 0;
            $disbursement_html = '';

            foreach ($disbursements as $disbursement) {
                $disbursement_title = $disbursement->title;
                $disbursement_description = $disbursement->description;
                $disbursement_price = floatval($disbursement->unit_price);

                $disbursement_total += $disbursement_price;

                $disbursement_html .= '<tr>
                    <td width="10%" style="border: 0.2mm solid #ffffff; background-color: #F5F5F5; vertical-align: top;font-size: 0.8rem">' . __('Disbursement', 'my-easy-compta') . '</td>
                    <td width="45%" style="text-align: left; border: 0.2mm solid #ffffff; background-color: #F5F5F5; vertical-align: top;">
                        <div style="margin-bottom:6px; font-weight:bold; color: #111111; vertical-align: top;">' .
                nl2br($disbursement_title) . '</div>
                        ' . nl2br($disbursement_description) . '
                    </td>
                    <td width="15%" style="text-align: right;border: 0.2mm solid #ffffff; background-color: #F5F5F5; vertical-align: top;">&nbsp;</td>
                    <td width="10%" style="text-align: center;border: 0.2mm solid #ffffff; background-color: #F5F5F5; vertical-align: top;">&nbsp;</td>';
                if ($this->vat_active == 1) {
                    $disbursement_html .= '<td width="10%" style="text-align: center;border: 0.2mm solid #ffffff; background-color: #F5F5F5; vertical-align: top;">&nbsp;</td>';
                }
                if ($discount_exist) {
                    $disbursement_html .= '<td width="10%" style="text-align: center;border: 0.2mm solid #ffffff; background-color: #F5F5F5; vertical-align: top;">&nbsp;</td>';
                }
                $disbursement_html .= '<td width="15%" style="text-align: right;border: 0.2mm solid #ffffff; background-color: #F5F5F5; vertical-align: top;">
                        ' . $this->positionCurrency($this->formatAmount($disbursement_price), $default_currency_symbol->symbol) . '
                    </td>
                </tr>';
            }

            $items_html .= $disbursement_html;
        }

        $html .= $items_html;

        if ($sub_total == $sub_total_discounted) {
            $html .= '<tr>';

            if ($this->vat_active == 1) {
                if ($discount_exist) {
                    $html .= '<td colspan="3" style="background-color:#ffffff;"></td>';
                } else {
                    $html .= '<td colspan="2" style="background-color:#ffffff;"></td>';
                }
            } else {
                if ($discount_exist) {
                    $html .= '<td colspan="2" style="background-color:#ffffff;"></td>';
                } else {
                    $html .= '<td colspan="1" style="background-color:#ffffff;"></td>';
                }
            }
            $html .= '<td colspan="2"
                    style="border: 0.2mm solid #ffffff; background-color: #F5F5F5;font-size: 8pt; color: #111111;">
                    <strong>' . __('Subtotal', 'my-easy-compta') . '</strong></td>
                <td colspan="2"
                    style="border: 0.2mm solid #ffffff; background-color: #F5F5F5;font-weight: bold; color: #111111; text-align: right;">
                    ' . $this->positionCurrency($this->formatAmount($sub_total), $default_currency_symbol->symbol) . '
                </td>
            </tr>';
        } else {
            $html .= ' <tr>';
            if ($this->vat_active == 1) {
                if ($discount_exist) {
                    $html .= '<td colspan="3" style="background-color:#ffffff;"></td>';
                } else {
                    $html .= '<td colspan="2" style="background-color:#ffffff;"></td>';
                }
            } else {
                if ($discount_exist) {
                    $html .= '<td colspan="2" style="background-color:#ffffff;"></td>';
                } else {
                    $html .= '<td colspan="1" style="background-color:#ffffff;"></td>';
                }
            }
            $html .= '<td colspan="2"
                    style="border: 0.2mm solid #ffffff; background-color: #F5F5F5;font-size: 8pt; color: #111111;">
                    <strong>' . __('Subtotal', 'my-easy-compta') . '</strong></td>
                <td colspan="2"
                    style="border: 0.2mm solid #ffffff; background-color: #F5F5F5;font-weight: bold; color: #111111; text-align: right;">
                    <span style="text-decoration: line-through">' .
            $this->positionCurrency($this->formatAmount($sub_total), $default_currency_symbol->symbol) .
            '</span><br />' . $this->positionCurrency($this->formatAmount($sub_total_discounted),
                $default_currency_symbol->symbol) . '</td>
            </tr>';
        }

        if ($this->vat_active == 1) {
            foreach ($tva_totaux as $rate => $amount) {
                $html .= '<tr>';
                if ($discount_exist) {
                    $html .= '<td colspan="3" style="background-color:#ffffff;"></td>';
                } else {
                    $html .= '<td colspan="2" style="background-color:#ffffff;"></td>';
                }
                $html .= '<td colspan="2"
                    style="border: 0.2mm solid #ffffff; background-color: #F5F5F5;font-size: 8pt; color: #111111;">
                    <strong>' . __('Tax', 'my-easy-compta') . ' (' . $rate . '%)</strong></td>
                <td colspan="2"
                    style="border: 0.2mm solid #ffffff; background-color: #F5F5F5;font-weight: bold; color: #111111; text-align: right;">
                    ' . $this->positionCurrency($this->formatAmount($amount), $default_currency_symbol->symbol) . '</td>
            </tr>';
            }
        }
        if (isset($shipping_fees) && floatval($shipping_fees) > 0) {
            $shipping_amount = floatval($shipping_fees);
            $balance_due += $shipping_amount;
        }

        if (isset($shipping_fees) && floatval($shipping_fees) > 0) {
            $html .= '<tr>';

            if ($this->vat_active == 1) {
                if ($discount_exist) {
                    $html .= '<td colspan="3" style="background-color:#ffffff;"></td>';
                } else {
                    $html .= '<td colspan="2" style="background-color:#ffffff;"></td>';
                }
            } else {
                if ($discount_exist) {
                    $html .= '<td colspan="2" style="background-color:#ffffff;"></td>';
                } else {
                    $html .= '<td colspan="1" style="background-color:#ffffff;"></td>';
                }
            }

            $html .= '<td colspan="2"
                        style="border: 0.2mm solid #ffffff; background-color: #F5F5F5;font-size: 8pt; color: #111111;">
                        <strong>' . __('Shipping fees', 'my-easy-compta') . '</strong></td>
                    <td colspan="2"
                        style="border: 0.2mm solid #ffffff; background-color: #F5F5F5;font-weight: bold; color: #111111; text-align: right;">
                        ' . $this->positionCurrency($this->formatAmount($shipping_fees), $default_currency_symbol->symbol) . '
                    </td>
                </tr>';
        }

        if ($this->active_disbursements == 1) {
            $html .= '<tr>';
            if ($this->vat_active == 1) {
                if ($discount_exist) {
                    $html .= '<td colspan="3" style="background-color:#ffffff;"></td>';
                } else {
                    $html .= '<td colspan="2" style="background-color:#ffffff;"></td>';
                }
            } else {
                if ($discount_exist) {
                    $html .= '<td colspan="2" style="background-color:#ffffff;"></td>';
                } else {
                    $html .= '<td colspan="1" style="background-color:#ffffff;"></td>';
                }
            }
            $html .= '<td colspan="2" style="border: 0.2mm solid #ffffff; background-color: #F5F5F5;font-size: 8pt; color: #111111;">
                        <strong>' . __('Total disbursements', 'my-easy-compta') . '</strong>
                    </td>
                    <td colspan="2" style="border: 0.2mm solid #ffffff; background-color: #F5F5F5;font-weight: bold; color: #111111; text-align: right;">
                        ' . $this->positionCurrency($this->formatAmount($disbursement_total), $default_currency_symbol->symbol) . '
                    </td>
                </tr>';

            $balance_due += $disbursement_total;
        }

        $html .= '<tr>';

        if ($this->vat_active == 1) {
            if ($discount_exist) {
                $html .= '<td colspan="3" style="background-color:#ffffff;"></td>';
            } else {
                $html .= '<td colspan="2" style="background-color:#ffffff;"></td>';
            }
        } else {
            if ($discount_exist) {
                $html .= '<td colspan="2" style="background-color:#ffffff;"></td>';
            } else {
                $html .= '<td colspan="1" style="background-color:#ffffff;"></td>';
            }
        }

        $html .= '<td colspan="2"
                    style="border: 0.2mm solid #ffffff; background-color: #F5F5F5;font-size: 8pt; color: #111111; background-color: ' . $global_color . '; color:#ffffff;">
                    <strong>' . __('Total', 'my-easy-compta') . '</strong></td>
                <td colspan="2"
                    style="border: 0.2mm solid #ffffff; background-color: #F5F5F5;font-weight: bold; color: #111111; text-align: right; background-color: ' . $global_color . '; color:#ffffff;">
                    ' . $this->positionCurrency($this->formatAmount($balance_due), $default_currency_symbol->symbol) . '
                </td>
            </tr>';

        $html .= '
        </tbody>
    </table>';
        $html .= '<div style="page-break-before: always; margin-top:40px; font-family: dejavusanscondensed;font-size: 8pt;line-height: 13pt;color: #777777;">';
        $html .= '<div style="margin-top:40px; font-family: dejavusanscondensed;font-size: 8pt;line-height: 13pt;color: #777777;">
        <h4
            style="padding:5px 0; color: #111111; border-bottom: 0.2mm solid ' . $global_color . '; font-size:9pt; text-transform: uppercase;">
            ' . __('Conditions terms', 'my-easy-compta') . '</h4>';

        if ($invoice_pyament_conditions) {
            $html .= '<strong>' . __('Payment conditions', 'my-easy-compta') . ' : </strong> ' . $invoice_pyament_conditions . '<br />';
        }
        if ($invoice_pyament_mode) {
            $html .= '<strong>' . __('Payment mode', 'my-easy-compta') . ' : </strong> ' . $invoice_pyament_mode . '<br />';
        }
        $html .= $terms . '</div>';

        if ($invoice_bic && $invoice_iban) {
            $html .= '<div style="margin-top:40px; font-family: dejavusanscondensed;font-size: 8pt;line-height: 13pt;color: #777777;">
            <h4
                style="padding:5px 0; color: #111111; border-bottom: 0.2mm solid ' . $global_color . '; font-size:9pt; text-transform: uppercase;">
                ' . __('RIB', 'my-easy-compta') . '</h4>';

            if ($invoice_iban) {
                $html .= '<strong>' . __('IBAN', 'my-easy-compta') . ' : </strong> ' . $invoice_iban . '<br />';
            }
            if ($invoice_bic) {
                $html .= '<strong>' . __('BIC', 'my-easy-compta') . ' : </strong> ' . $invoice_bic . '<br />';
            }
            $html .= '</div>';
        }

        if ($type == 'quote') {
            $file_path = "";
            if ($this->signature_active == 1 && $data->signed == 1 && !empty($data->file_sign)) {
                $upload_dir = wp_upload_dir();
                $file_path = $upload_dir['basedir'] . '/signatures/' . $data->file_sign;
            }
            $html .= '<div
        style="font-family: dejavusanscondensed;font-size: 10pt;line-height: 13pt;color: #777777;margin-top: 50px; border: 0.2emm solid #111111; padding: 0px 20px 50px; width: 350px; float: right;">
        <h4 style="font-size:9pt;">' . __('Agreement & signature', 'my-easy-compta') . '</h4>';
            if ($file_path) {
                $html .= '<img style="max-width: 100%; max-height: 100%" src="' . $file_path . '" />';
            }
            $html .= '
    </div>';
        }
        $html .= '
</body>

</html>';

        return $html;
    }

/**
 * @param mixed $date
 *
 * @return [type]
 */
    private function formatDate($date)
    {
        return gmdate($this->date_format, strtotime($date));
    }

/**
 * @param mixed $amount
 *
 * @return [type]
 */
    private function formatAmount($amount)
    {
        return number_format((float) $amount, 2, '.', ' ');
    }

/**
 * @param mixed $amount
 * @param mixed $symbol
 *
 * @return [type]
 */
    private function positionCurrency($amount, $symbol)
    {
        return ($this->currency_position === 'before') ? $symbol . ' ' . $amount : $amount . ' ' . $symbol;
    }
}
