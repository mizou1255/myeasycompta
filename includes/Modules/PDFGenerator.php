<?php

namespace ECWP\Admin\PDF;

require ECWP_PATH . '/vendor/autoload.php';

class PDFGenerator
{
    private $wpdb;
    private $settings_array = [];
    private $invoice_color;
    private $quote_color;
    private $logo_path;
    private $logo_width;
    private $logo_mentions;
    private $company_name;
    private $company_address;
    private $postal_code;
    private $city;
    private $country;
    private $phone;
    private $fax;
    private $siret;
    private $invoice_terms;
    private $invoice_footer;
    private $date_format;
    private $vat_active;
    private $default_vat;
    private $currency_position;
    private $vat_rate = 0;
    private $signature_active = 0;

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
        $this->quote_color = $this->settings_array['quote_color'] ?? '#ff6a00';
        $this->company_name = $this->settings_array['company_name'] ?? '';
        $this->company_address = $this->settings_array['company_address'] ?? '';
        $this->postal_code = $this->settings_array['postal_code'] ?? '';
        $this->city = $this->settings_array['city'] ?? '';
        $this->country = $this->settings_array['country'] ?? '';
        $this->phone = $this->settings_array['phone'] ?? '';
        $this->fax = $this->settings_array['fax'] ?? '';
        $this->siret = $this->settings_array['company_code'] ?? '';
        $this->invoice_terms = $this->settings_array['invoice_terms'] ?? '';
        $this->invoice_footer = $this->settings_array['invoice_footer'] ?? '';
        $this->date_format = $this->convertFormatDate($this->settings_array['date_format']) ?? 'd-m-Y';
        $this->vat_active = $this->settings_array['vat_active'] ?? '1';
        $this->default_vat = $this->settings_array['default_vat'] ?? '1';
        $this->currency_position = $this->settings_array['currency_position'] ?? 'after';
        $this->signature_active = $this->settings_array['easy_compta_signature_addon_active'] ?? 0;
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
    public function generateInvoicePDF($invoice_id, $type = 'show', $currency_id)
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

        /* if ($this->vat_active == 1) {
        $company_vat = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", ECWP_TABLE_VATS, $this->default_vat));
        $this->vat_rate = $company_vat->rate;
        } */

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
            $pdf_file_path = $pdf_dir . '/' . $invoice->invoice_number . '.pdf';
            $mpdf->Output($pdf_file_path, \Mpdf\Output\Destination::FILE);
            return $pdf_file_path;
        } else {
            $mpdf->Output($invoice->invoice_number . '.pdf', 'I');
        }
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
        /* if ($this->vat_active == 1) {
        $company_vat = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", ECWP_TABLE_VATS, $this->default_vat));
        $this->vat_rate = $company_vat->rate;
        } */

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
        if ($type == 'invoice') {
            $number = $encrypt->decrypt($data->invoice_number);
            $global_color = $this->invoice_color;
            $show_type = __('Invoice', 'my-easy-compta');
            $payment_type = __('Payment date', 'my-easy-compta');
            $date_show_type = __('Created at', 'my-easy-compta');
            $date_type = $this->formatDate($data->created_at);
        } else {
            $number = $data->quote_number;
            $global_color = $this->quote_color;
            $date_type = $this->formatDate($data->provisional_start_date);
        }

        $html = '<html>
        <head><style>
        body {
            font-family: dejavusanscondensed;
            font-size: 10pt;
            line-height: 13pt;
            color: #777777;
        }
        p {
            margin: 4pt 0 0 0;
        }
        td {
            vertical-align: top;
        }
        .items td {
            border: 0.2mm solid #ffffff;
            background-color: #F5F5F5;
        }
        table thead td {
            vertical-align: bottom;
            text-align: center;
            text-transform: uppercase;
            font-size: 7pt;
            font-weight: bold;
            background-color: #FFFFFF;
            color: #111111;
        }
        table thead td {
            border-bottom: 0.2mm solid ' . $global_color . ';
        }
        table .last td  {
            border-bottom: 0.2mm solid ' . $global_color . ';
        }
        table .first td  {
            border-top: 0.2mm solid ' . $global_color . ';
        }
        teable.items > tr-items tr:last-child {
            border-bottom: 0.2mm solid ' . $global_color . ';
        }
        .watermark {
            text-transform: uppercase;
            font-weight: bold;
            position: absolute;
            left: 100px;
            top: 400px;
        }
    </style>
</head>
<body>
<htmlpagefooter name="myfooter">
    <div style="font-size: 9pt; text-align: center; padding-top: 3mm; width:100%;">
    ' . $this->invoice_footer . '
    </div>
</htmlpagefooter>

<sethtmlpagefooter name="myfooter" value="on" />
    <div>
        <table width="100%">
            <tr>
                <td width="60%" height="100">
                    <img style="width: ' . $this->logo_width . 'px;" src="' . $this->logo_path . '" /><br /><br />
                    <p>' . $this->logo_mentions . '</p>
                </td>
                <td width="40%" style="text-align: right;">
                    <div style="font-weight: bold; color: #111111; font-size: 20pt; text-transform: uppercase;">' . $show_type . '</div>
                    <table>
                        <tr>
                            <td width="10%">&nbsp;</td>
                            <td width="55%" style="color: ' . $global_color . '; text-align: left; font-size: 9pt; text-transform: uppercase;">' . __('Reference No', 'my-easy-compta') . ':</td>
                            <td width="25%" style="text-align: right; font-size: 9pt;">' . $number . '</td>
                        </tr>
                        <tr>
                            <td width="10%">&nbsp;</td>
                            <td width="55%" style="color: ' . $global_color . '; text-align: left; font-size: 9pt; text-transform: uppercase;">' . $date_show_type . ' :</td>
                            <td width="25%" style="text-align: right; font-size: 9pt;">' . $date_type . '</td>
                        </tr>
                        <tr>
                            <td width="10%">&nbsp;</td>
                            <td width="55%" style="color: ' . $global_color . '; text-align: left; font-size: 9pt; text-transform: uppercase;">' . $payment_type . ' :</td>
                            <td width="25%" style="text-align: right; font-size: 9pt;">' . $this->formatDate($data->due_date) . '</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-bottom: 20px; margin-top: 30px;">
        <table width="100%" cellpadding="10" style="vertical-align: top;">
            <tr>
                <td width="45%" style="border-bottom:0.2mm solid ' . $global_color . '; font-size: 9pt; font-weight:bold; color: ' . $global_color . '; text-transform: uppercase;">' . __('Received From', 'my-easy-compta') . '</td>
                <td width="10%">&nbsp;</td>
                <td width="45%" style="border-bottom:0.2mm solid ' . $global_color . '; font-size: 9pt; font-weight:bold; color: ' . $global_color . '; text-transform: uppercase;">' . __('Recipient', 'my-easy-compta') . '</td>
            </tr>
            <tr>
                <td width="45%">
                    <span style="font-size: 11pt; font-weight: bold; color: #111111;">' . $this->company_name . '</span><br/>
                    ' . $this->company_address . '<br>
                    ' . $this->city . ', ' . $this->postal_code . '<br>
                   ' . $this->country . '<br>
                    ' . $this->phone . '<br>
                    ' . $this->fax . '<br>';

        if ($this->siret) {
            $html .= '<b>SIRET : </b>' . $this->siret . '<br>';
        }
        $html .= ' </td>
                <td width="10%">&nbsp;</td>
                <td width="45%">
                    <span style="font-size: 11pt; font-weight: bold; color: #111111;">' . $client->company_name . '</span><br/>
                    ' . $client->address . '<br>
                    ' . $client->postal_code . ', ' . $client->city . '<br>
                    ' . $client->country . '<br>
                    ' . $client->phone . '<br>';

        if ($client->siren_number) {
            $html .= '<b>SIREN : </b>' . $client->siren_number . '<br>';
        }
        $html .= '
                </td>
            </tr>
        </table>
    </div>

    <table class="items" width="100%" style="border-spacing:3px; font-size: 9pt; border-collapse: collapse;" cellpadding="10">
        <thead>
        <tr>
            <td width="10%">' . __('Ref', 'my-easy-compta') . '</td>
            <td width="45%" style="text-align: left;">' . __('Item name', 'my-easy-compta') . '</td>
            <td width="10%">' . __('Qty', 'my-easy-compta') . '</td>
            <td width="15%">' . __('Unit price', 'my-easy-compta') . '</td>
            <td width="15%">' . __('Vat', 'my-easy-compta') . '</td>
            <td width="15%">' . __('Discount', 'my-easy-compta') . '</td>
            <td width="15%">' . __('Total', 'my-easy-compta') . '</td>
        </tr>
        </thead>
        <tbody>';
        $tva_totaux = [];
        foreach ($items as $item) {
            if ($type == 'invoice') {
                // Déchiffrer les données uniquement pour les factures
                $quantity = intval($encrypt->decrypt($item->quantity));
                $unit_price = floatval($encrypt->decrypt($item->unit_price));
                $discount_percentage = intval($encrypt->decrypt($item->discount));
                $vat_rate = intval($encrypt->decrypt($item->vat_rate));
                $item_ref = $encrypt->decrypt($item->item_ref);
                $item_name = $encrypt->decrypt($item->item_name);
                $item_description = $encrypt->decrypt($item->item_description);
            } else {
                // Utiliser les données en clair pour les devis
                $quantity = intval($item->quantity);
                $unit_price = floatval($item->unit_price);
                $discount_percentage = intval($item->discount);
                $vat_rate = intval($item->vat_rate);
                $item_ref = $item->item_ref;
                $item_name = $item->item_name;
                $item_description = $item->item_description;
            }

            $item_total = $quantity * $unit_price;

            if ($this->vat_active == 1) {
                $item_total_vat = ($item_total * $vat_rate) / 100;
                $discount_amount_with_vat = (($item_total + $item_total_vat) * $discount_percentage) / 100;
                $total_after_discount_with_vat = $item_total - $discount_amount_with_vat + $item_total_vat;
                $sub_total_discounted_with_vat += $total_after_discount_with_vat;

                if (!isset($tva_totaux[$vat_rate])) {
                    $tva_totaux[$vat_rate] = 0;
                }
                $tva_totaux[$vat_rate] += $item_total_vat;
            }

            $discount_amount = ($item_total * $discount_percentage) / 100;
            $total_after_discount = $item_total - $discount_amount;
            $sub_total += $item_total;
            $sub_total_discounted += $total_after_discount;

            $html .= '<tr>
            <td width="10%">' . nl2br($item_ref) . '</td>
            <td width="45%" style="text-align: left;">
                <div style="margin-bottom:6px; font-weight:bold; color: #111111;">' . nl2br($item_name) . '</div>
                ' . nl2br($item_description) . '
            </td>
            <td width="10%" style="text-align: center;">' . $quantity . '</td>
            <td width="15%" style="text-align: right;">' . $this->positionCurrency($this->formatAmount($unit_price), $default_currency_symbol->symbol) . '</td>
            <td width="15%" style="text-align: right;">' . $this->positionCurrency($this->formatAmount($item_total_vat), $default_currency_symbol->symbol) . '<br /><small>' . $vat_rate . '%</small></td>
            <td width="15%" style="text-align: right;">' . $this->positionCurrency($this->formatAmount($discount_amount), $default_currency_symbol->symbol) . '<br /><small>' . $discount_percentage . '%</small></td>
            <td width="15%" style="text-align: right;">' . $this->positionCurrency($this->formatAmount($total_after_discount_with_vat), $default_currency_symbol->symbol) . '</td>
            </tr>';

        }

        if ($this->vat_active == 1) {
            $balance_due = $sub_total_discounted_with_vat;
        } else {
            $balance_due = $sub_total_discounted;
        }

        if ($sub_total == $sub_total_discounted) {
            $html .= '<tr>
                    <td colspan="3" style="background-color:#ffffff;"></td>
                    <td colspan="2" style="font-size: 8pt; color: #111111;"><strong>' . __('Subtotal', 'my-easy-compta') . '</strong></td>
                    <td  colspan="2" style="font-weight: bold; color: #111111; text-align: right;">' . $this->positionCurrency($this->formatAmount($sub_total), $default_currency_symbol->symbol) . '</td>
                </tr>';
        } else {
            $html .= '
            <tr>
                <td colspan="3" style="background-color:#ffffff;"></td>
                <td colspan="2" style="font-size: 8pt; color: #111111;"><strong>' . __('Subtotal', 'my-easy-compta') . '</strong></td>
                <td colspan="2" style="font-weight: bold; color: #111111; text-align: right;"><span style="text-decoration: line-through">' . $this->positionCurrency($this->formatAmount($sub_total), $default_currency_symbol->symbol) . '</span><br/>' . $this->positionCurrency($this->formatAmount($sub_total_discounted), $default_currency_symbol->symbol) . '</td>
            </tr>';
        }

        if ($this->vat_active == 1) {
            foreach ($tva_totaux as $rate => $amount) {
                $html .= '<tr>
                    <td colspan="3" style="background-color:#ffffff;"></td>
                    <td colspan="2" style="font-size: 8pt; color: #111111;"><strong>' . __('Tax', 'my-easy-compta') . ' (' . $rate . '%)</strong></td>
                    <td colspan="2" style="font-weight: bold; color: #111111; text-align: right;">' . $this->positionCurrency($this->formatAmount($amount), $default_currency_symbol->symbol) . '</td>
                </tr>';
            }
        }
        $html .= '
        <tr>
            <td colspan="3" style="background-color:#ffffff;"></td>
            <td colspan="2" style="font-size: 8pt; color: #111111; background-color: ' . $global_color . '; color:#ffffff;"><strong>' . __('Total', 'my-easy-compta') . '</strong></td>
            <td colspan="2" style="font-weight: bold; color: #111111; text-align: right; background-color: ' . $global_color . '; color:#ffffff;">' . $this->positionCurrency($this->formatAmount($balance_due), $default_currency_symbol->symbol) . '</td>
        </tr>';

        $html .= '</tbody></table>
            <div style="margin-top:40px;">
                <h4 style="padding:5px 0; color: #111111; border-bottom: 0.2mm solid ' . $global_color . '; font-size:9pt; text-transform: uppercase;">' . __('Conditions terms', 'my-easy-compta') . '</h4>
                ' . $this->invoice_terms . '
            </div>';
        if ($type == 'quote') {
            $file_path = "";
            if ($this->signature_active == 1 && $data->signed == 1 && !empty($data->file_sign)) {
                $upload_dir = wp_upload_dir();
                $file_path = $upload_dir['basedir'] . '/signatures/' . $data->file_sign;
            }
            $html .= '<div style="margin-top: 50px; border: 0.2emm solid #111111; padding: 0px 20px 50px; width: 350px; float: right;">
                <h4 style="font-size:9pt;">' . __('Agreement & signature', 'my-easy-compta') . '</h4>';
            if ($file_path) {
                $html .= '<img style="max-width: 100%; max-height: 100%" src="' . $file_path . '" />';
            }
            $html .= '</div>';
        }
        $html .= '</body></html>';

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
