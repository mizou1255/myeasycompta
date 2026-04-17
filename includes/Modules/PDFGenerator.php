<?php

namespace ECWP\Admin\PDF;

require_once ECWP_PATH . '/vendor/autoload.php';

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
    private $pdf_template;
    private $pdf_primary_color;
    private $pdf_secondary_color;
    // Document specific settings
    private $invoice_template;
    private $invoice_font;
    private $credit_template;
    private $credit_font;
    private $quote_template;
    private $quote_font;
    private $current_font; // To store font of current document being generated
    private $pdf_font;
    private $pdf_footer_text;

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
        $settings_table = ECWP_TABLE_SETTINGS;
        $settings = $wpdb->get_results("SELECT * FROM {$settings_table}");

        foreach ($settings as $setting) {
            $this->settings_array[$setting->meta_key] = $setting->meta_value;
        }
        // Gérer logo_url et logo_path (priorité à logo_url)
        $logo_url = $this->settings_array['logo_url'] ?? '';
        $logo_path = $this->settings_array['logo_path'] ?? '';

        // Si logo_url est défini, l'utiliser et convertir en chemin absolu si c'est une URL locale
        if (!empty($logo_url)) {
            $upload_dir = wp_upload_dir();
            // Si c'est une URL WordPress locale, convertir en chemin absolu
            if (strpos($logo_url, $upload_dir['baseurl']) === 0) {
                // C'est une URL locale WordPress, convertir en chemin absolu
                $this->logo_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $logo_url);
            } elseif (filter_var($logo_url, FILTER_VALIDATE_URL)) {
                // External URL: only allow safe protocols (http/https).
                $scheme = wp_parse_url($logo_url, PHP_URL_SCHEME);
                if (in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
                    $this->logo_path = esc_url_raw($logo_url);
                } else {
                    $this->logo_path = '';
                }
            } else {
                // Already a local path — keep as-is (no URL parsing needed).
                $this->logo_path = $logo_url;
            }
        } elseif (!empty($logo_path)) {
            // Utiliser logo_path si logo_url n'est pas défini
            $this->logo_path = $logo_path;
        } else {
            $this->logo_path = '';
        }

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

        $this->invoice_template = $this->settings_array['invoice_pdf_template'] ?? 'modern';
        $this->invoice_font = $this->settings_array['invoice_pdf_font'] ?? 'dejavusanscondensed';

        $this->credit_template = $this->settings_array['credit_pdf_template'] ?? 'modern';
        $this->credit_font = $this->settings_array['credit_pdf_font'] ?? 'dejavusanscondensed';

        $this->quote_template = $this->settings_array['quote_pdf_template'] ?? 'modern';
        $this->quote_font = $this->settings_array['quote_pdf_font'] ?? 'dejavusanscondensed';
        // PDF Customization
        $this->pdf_template = $this->settings_array['pdf_template'] ?? 'modern';
        $this->pdf_primary_color = $this->settings_array['pdf_primary_color'] ?? '#7c3aed';
        $this->pdf_secondary_color = $this->settings_array['pdf_secondary_color'] ?? '#4b5563';
        $this->pdf_font = $this->settings_array['pdf_font'] ?? 'dejavusanscondensed';
        $this->pdf_footer_text = $this->settings_array['pdf_footer_text'] ?? '';
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
        $invoices_table = ECWP_TABLE_INVOICES;
        $invoice_elements_table = ECWP_TABLE_INVOICE_ELEMENTS;
        $articles_categories_table = ECWP_TABLE_ARTICLES_CATEGORIES;
        $clients_table = ECWP_TABLE_CLIENTS;

        $invoice = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$invoices_table} WHERE id = %d", $invoice_id));
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
                {$invoice_elements_table} ie
            LEFT JOIN
                {$articles_categories_table} ac
            ON
                ie.item_category = ac.id
            WHERE
                ie.invoice_id = %d
            ORDER BY
                ie.item_order ASC",
                $invoice_id
            ),
            OBJECT
        );
        $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$clients_table} WHERE id = %d", $invoice->client_id));

        if ($currency_id) {
            $default_currency_symbol = $this->getDefaultCurrencySymbol($currency_id);
        } else {
            $default_currency_symbol = $this->getDefaultCurrencySymbol($client->currency_id);
        }

        if ($currency_id != null && $currency_id !== $client->currency_id) {
            $enc = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
            $exchange_rate = floatval($enc->decrypt($invoice->exchange_rate));
            foreach ($items as &$item) {
                $plain_price = floatval($enc->decrypt($item->unit_price)) * $exchange_rate;
                $item->unit_price = $enc->encrypt($plain_price);
            }
            unset($item);
        }

        $html = $this->generateHTML('invoice', $invoice, $items, $client, $default_currency_symbol);
        $mpdf = new \Mpdf\Mpdf([
            'margin_left'   => 12,
            'margin_right'  => 12,
            'margin_top'    => 0,
            'margin_bottom' => 18,
            'margin_header' => 0,
            'margin_footer' => 8,
            'default_font_size' => 9,
            'default_font'  => $this->current_font,
            'mode'   => 'utf-8',
            'format' => 'A4',
        ]);

        $mpdf->SetAutoPageBreak(true, 20);

        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        $invoice_number = $encrypt->decrypt($invoice->invoice_number);

        $mpdf->SetTitle(htmlspecialchars($invoice_number));
        $mpdf->SetAuthor(htmlspecialchars($client->company_name));
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
        $credits_table = ECWP_TABLE_CREDITS;
        $invoices_table = ECWP_TABLE_INVOICES;
        $credits = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, i.id, i.invoice_number, i.client_id, i.total_amount, i.exchange_rate, i.due_date
                                            FROM {$credits_table} c
                                            LEFT JOIN {$invoices_table} i ON c.invoice_id = i.id
                                            WHERE c.id = %d",
            $credit_id
        ));

        if (!$credits) {
            return new \WP_Error('credit_not_found', __('Credit not found', 'my-easy-compta'), array('status' => 404));
        }
        $invoice_elements_table = ECWP_TABLE_INVOICE_ELEMENTS;
        $articles_categories_table = ECWP_TABLE_ARTICLES_CATEGORIES;
        $credits_table_for_items = ECWP_TABLE_CREDITS;
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
                {$invoice_elements_table} ie
             LEFT JOIN
                {$articles_categories_table} ac ON ie.item_category = ac.id
             LEFT JOIN
                {$credits_table_for_items} credits ON ie.invoice_id = credits.invoice_id
             WHERE
                ie.invoice_id = %d
             ORDER BY
                ie.item_order ASC",
                $credits->invoice_id
            ),
            OBJECT
        );

        $clients_table = ECWP_TABLE_CLIENTS;
        $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$clients_table} WHERE id = %d", $credits->client_id));

        if ($currency_id) {
            $default_currency_symbol = $this->getDefaultCurrencySymbol($currency_id);
        } else {
            $default_currency_symbol = $this->getDefaultCurrencySymbol($client->currency_id);
        }
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        $invoice_number = $encrypt->decrypt($credits->invoice_number);

        if ($currency_id != null && $currency_id !== $client->currency_id) {
            $exchange_rate = floatval($encrypt->decrypt($credits->exchange_rate));
            foreach ($items as &$item) {
                $plain_price = floatval($encrypt->decrypt($item->unit_price)) * $exchange_rate;
                $item->unit_price = $encrypt->encrypt($plain_price);
            }
            unset($item);
        }

        $html = $this->generateHTML('credit_invoice', $credits, $items, $client, $default_currency_symbol);
        $mpdf = new \Mpdf\Mpdf([
            'margin_left'   => 12,
            'margin_right'  => 12,
            'margin_top'    => 0,
            'margin_bottom' => 18,
            'margin_header' => 0,
            'margin_footer' => 8,
            'default_font_size' => 9,
            'default_font'  => $this->current_font,
            'mode'   => 'utf-8',
            'format' => 'A4',
        ]);

        $mpdf->SetAutoPageBreak(true, 20);

        $mpdf->SetTitle(htmlspecialchars($invoice_number . ' - Credit Note'));
        $mpdf->SetAuthor(htmlspecialchars($client->company_name));
        $mpdf->WriteHTML($html);

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
        $quotes_table = ECWP_TABLE_QUOTES;
        $quote_elements_table = ECWP_TABLE_QUOTE_ELEMENTS;
        $articles_categories_table = ECWP_TABLE_ARTICLES_CATEGORIES;
        $clients_table = ECWP_TABLE_CLIENTS;

        $quote = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$quotes_table} WHERE id = %d", $quote_id));
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
{$quote_elements_table} ie
LEFT JOIN
{$articles_categories_table} ac
ON
ie.item_category = ac.id
WHERE
ie.quote_id = %d
ORDER BY
ie.item_order ASC",
                $quote_id
            ),
            OBJECT
        );
        $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$clients_table} WHERE id = %d", $quote->client_id));
        $default_currency_symbol = $this->getDefaultCurrencySymbol($client->currency_id);

        $html = $this->generateHTML('quote', $quote, $items, $client, $default_currency_symbol);

        $mpdf = new \Mpdf\Mpdf([
            'mode'   => 'utf-8',
            'format' => 'A4',
            'margin_left'   => 12,
            'margin_right'  => 12,
            'margin_top'    => 0,
            'margin_bottom' => 18,
            'margin_header' => 0,
            'margin_footer' => 8,
            'default_font_size' => 9,
            'default_font' => 'dejavusanscondensed',
        ]);

        $mpdf->SetTitle(htmlspecialchars($quote->quote_number));
        $mpdf->SetAuthor(htmlspecialchars($client->company_name));

        // Désactiver l'auto page break qui force les sauts de page
        $mpdf->SetAutoPageBreak(true, 20);

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
        $currencies_table = ECWP_TABLE_CURRENCY;
        return $wpdb->get_row($wpdb->prepare("SELECT symbol FROM {$currencies_table} WHERE id = %d", $client_currency_id));
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
        $template = 'modern';
        $this->current_font = 'dejavusanscondensed';

        if ($type == 'invoice') {
            $template = $this->invoice_template;
            $this->current_font = $this->invoice_font;
        } elseif ($type == 'credit_invoice') {
            $template = $this->credit_template;
            $this->current_font = $this->credit_font;
        } else {
            $template = $this->quote_template;
            $this->current_font = $this->quote_font;
        }

        if ($template === 'modern' || $template === 'minimal') {
            return $this->generateModernHTML($type, $data, $items, $client, $default_currency_symbol);
        }
        return $this->generateClassicHTML($type, $data, $items, $client, $default_currency_symbol);
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
    private function generateClassicHTML($type, $data, $items, $client, $default_currency_symbol)
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
                <td width="60%" height="100">';

        // Gérer l'affichage du logo
        if (!empty($this->logo_path)) {
            // Vérifier si c'est une URL ou un chemin local
            $logo_src = $this->logo_path;
            if (!filter_var($this->logo_path, FILTER_VALIDATE_URL)) {
                // C'est un chemin local, vérifier qu'il existe
                if (file_exists($this->logo_path)) {
                    // mPDF peut utiliser les chemins absolus directement
                    $logo_src = $this->logo_path;
                } else {
                    // Le fichier n'existe pas, ne pas afficher le logo
                    $logo_src = '';
                }
            }

            if (!empty($logo_src)) {
                $logo_width_style = !empty($this->logo_width) ? 'width: ' . intval($this->logo_width) . 'px;' : '';
                $html .= '<img style="' . $logo_width_style . '" src="' . htmlspecialchars($logo_src) . '" /><br /><br />';
            }
        }

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
                    ' . $this->positionCurrency(
                        $this->formatAmount($total_after_discount_with_vat),
                        $default_currency_symbol->symbol
                    ) . '</td>
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
            $disbursements_table = ECWP_TABLE_DISBURSEMENTS;
            $disbursements = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM {$disbursements_table} WHERE invoice_id = %d", $data->id)
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
                '</span><br />' . $this->positionCurrency(
                        $this->formatAmount($sub_total_discounted),
                        $default_currency_symbol->symbol
                    ) . '</td>
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
            $html .= '<strong>' . __('Payment conditions', 'my-easy-compta') . ' : </strong> ' . htmlspecialchars($invoice_pyament_conditions) . '<br />';
        }
        if ($invoice_pyament_mode) {
            $html .= '<strong>' . __('Payment mode', 'my-easy-compta') . ' : </strong> ' . htmlspecialchars($invoice_pyament_mode) . '<br />';
        }
        $html .= $terms . '</div>';

        if ($invoice_bic && $invoice_iban) {
            $html .= '<div style="margin-top:40px; font-family: dejavusanscondensed;font-size: 8pt;line-height: 13pt;color: #777777;">
            <h4
                style="padding:5px 0; color: #111111; border-bottom: 0.2mm solid ' . $global_color . '; font-size:9pt; text-transform: uppercase;">
                ' . __('RIB', 'my-easy-compta') . '</h4>';

            if ($invoice_iban) {
                $html .= '<strong>' . __('IBAN', 'my-easy-compta') . ' : </strong> ' . htmlspecialchars($invoice_iban) . '<br />';
            }
            if ($invoice_bic) {
                $html .= '<strong>' . __('BIC', 'my-easy-compta') . ' : </strong> ' . htmlspecialchars($invoice_bic) . '<br />';
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
    /**
     * @param mixed $type
     * @param mixed $data
     * @param mixed $items
     * @param mixed $client
     * @param mixed $default_currency_symbol
     *
     * @return [type]
     */
    private function generateModernHTML($type, $data, $items, $client, $default_currency_symbol)
    {
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        $sub_total                     = 0;
        $sub_total_discounted          = 0;
        $sub_total_discounted_with_vat = 0;
        $show_type          = __('Quote', 'my-easy-compta');
        $payment_type       = __('Due date', 'my-easy-compta');
        $date_show_type     = __('Date planned', 'my-easy-compta');
        $date_type          = '';
        $invoice_pyament_conditions = '';
        $invoice_pyament_mode       = '';
        $invoice_iban       = '';
        $invoice_bic        = '';
        $terms              = '';
        $footer             = '';
        $discount_exist     = false;

        if ($type === 'invoice') {
            $number         = $encrypt->decrypt($data->invoice_number);
            $show_type      = __('Invoice', 'my-easy-compta');
            $payment_type   = __('Payment date', 'my-easy-compta');
            $date_show_type = __('Created at', 'my-easy-compta');
            $date_type      = $this->formatDate($data->created_at);
            $terms          = $this->invoice_terms;
            $invoice_pyament_conditions = $this->payment_conditions;
            $invoice_pyament_mode       = $this->payment_mode;
            $invoice_iban   = $this->invoice_iban;
            $invoice_bic    = $this->invoice_bic;
            $footer         = $this->invoice_footer;
        } elseif ($type === 'credit_invoice') {
            $number         = $data->credit_number;
            $show_type      = __('Credit', 'my-easy-compta');
            $payment_type   = __('Payment date', 'my-easy-compta');
            $date_show_type = __('Created at', 'my-easy-compta');
            $date_type      = $this->formatDate($data->created_at);
            $terms          = $this->credit_terms;
            $footer         = $this->credit_footer;
        } else {
            $number    = $data->quote_number;
            $date_type = $this->formatDate($data->provisional_start_date);
            $terms     = $this->quote_terms;
            $footer    = $this->quote_footer;
        }

        if ($type === 'invoice') {
            $primary_color = !empty($this->invoice_color) ? $this->invoice_color : $this->pdf_primary_color;
        } elseif ($type === 'credit_invoice') {
            $primary_color = !empty($this->credit_color) ? $this->credit_color : $this->pdf_primary_color;
        } else {
            $primary_color = !empty($this->quote_color) ? $this->quote_color : $this->pdf_primary_color;
        }

        if (empty($footer) && !empty($this->pdf_footer_text)) {
            $footer = $this->pdf_footer_text;
        }

        $font_family = $this->current_font;

        // ── PRE-SCAN: detect discount ──
        foreach ($items as $_item) {
            $d = ($type === 'invoice' || $type === 'credit_invoice')
                ? intval($encrypt->decrypt($_item->discount))
                : intval($_item->discount);
            if ($d > 0) { $discount_exist = true; break; }
        }

        // ── COLUMN WIDTHS ──
        $has_vat      = ($this->vat_active == 1);
        $has_discount = $discount_exist;

        if ($has_vat && $has_discount) {
            $w_item = '36%'; $w_qty = '8%'; $w_price = '14%'; $w_vat = '14%'; $w_disc = '12%'; $w_total = '16%';
        } elseif ($has_vat) {
            $w_item = '42%'; $w_qty = '8%'; $w_price = '16%'; $w_vat = '16%'; $w_total = '18%';
        } elseif ($has_discount) {
            $w_item = '46%'; $w_qty = '8%'; $w_price = '16%'; $w_disc = '14%'; $w_total = '16%';
        } else {
            $w_item = '52%'; $w_qty = '8%'; $w_price = '18%'; $w_total = '22%';
        }

        // ══ PRÉ-CALCUL DU TOTAL pour affichage dans l'en-tête ══
        $pre_total = 0.0;
        foreach ($items as $_pi) {
            if ($type === 'invoice' || $type === 'credit_invoice') {
                $_q  = floatval($encrypt->decrypt($_pi->quantity));
                $_up = floatval($encrypt->decrypt($_pi->unit_price));
                $_d  = intval($encrypt->decrypt($_pi->discount));
                $_v  = intval($encrypt->decrypt($_pi->vat_rate));
            } else {
                $_q  = floatval($_pi->quantity);
                $_up = floatval($_pi->unit_price);
                $_d  = intval($_pi->discount);
                $_v  = intval($_pi->vat_rate);
            }
            $_it = $_q * $_up * (1 - $_d / 100);
            $pre_total += $has_vat ? $_it * (1 + $_v / 100) : $_it;
        }

        // ── CSS ──
        $css  = "body { font-family: '{$font_family}', sans-serif; color: #1e293b; font-size: 9.5pt; margin: 0; padding: 0; }";
        $css .= "table { border-collapse: collapse; }";

        $html = '<html><head><style>' . $css . '</style></head><body>';

        $logo_src = '';
        if (!empty($this->logo_path)) {
            $logo_src = $this->logo_path;
            if (!filter_var($this->logo_path, FILTER_VALIDATE_URL) && !file_exists($this->logo_path)) {
                $logo_src = '';
            }
        }

        // Espaceur top de page
        $html .= '<table width="100%" cellspacing="0" cellpadding="0"><tr><td style="border:none; height:18px; padding:0; font-size:1pt; line-height:18px;">&#160;</td></tr></table>';

        // Header blanc — barre couleur en bas uniquement
        $html .= '<table width="100%" cellspacing="0" cellpadding="0">';
        $html .= '<tr>';

        // Gauche : Logo + société
        $html .= '<td width="55%" valign="top" style="border: none; padding-bottom: 24px;">';
        if (!empty($logo_src)) {
            $lw = !empty($this->logo_width) ? intval($this->logo_width) : 120;
            $html .= '<img src="' . htmlspecialchars($logo_src) . '" style="max-width: ' . $lw . 'px; max-height: 55px; display: block; margin-bottom: 16px;" />';
        }
        if ($this->logo_mentions_active == 1 && !empty($this->logo_mentions)) {
            $html .= '<div style="font-size: 8.5pt; color: #475569; line-height: 2.4;">' . nl2br(htmlspecialchars($this->logo_mentions)) . '</div>';
        } else {
            $html .= '<div style="font-size: 12pt; font-weight: bold; color: #0f172a; margin-bottom: 8px;">' . htmlspecialchars($this->company_name) . '</div>';
            $html .= '<div style="font-size: 8.5pt; color: #64748b; line-height: 2.4;">';
            $html .= htmlspecialchars($this->company_address) . '<br/>';
            $html .= htmlspecialchars($this->postal_code) . ' ' . htmlspecialchars($this->city);
            if ($this->country)                                 $html .= ' ' . htmlspecialchars($this->country);
            if ($this->company_phone && $this->show_phone == 1) $html .= '<br/>Tél : '    . htmlspecialchars($this->company_phone);
            if ($this->siret)                                   $html .= '<br/>SIRET : '  . htmlspecialchars($this->siret);
            if ($this->tax_number)                              $html .= '<br/>N° TVA : ' . htmlspecialchars($this->tax_number);
            $html .= '</div>';
        }
        $html .= '</td>';

        // Droite : titre document + numéro + montant dû
        $html .= '<td width="45%" valign="top" style="border: none; text-align: right; padding-bottom: 24px;">';
        $html .= '<div style="font-size: 7pt; color: #94a3b8; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 6px;">' . htmlspecialchars($show_type) . '</div>';
        $html .= '<div style="font-size: 16pt; font-weight: bold; color: #0f172a; letter-spacing: 0.5px; margin-bottom: 20px;">' . htmlspecialchars($number) . '</div>';
        $html .= '<div style="font-size: 7pt; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">' . __('Montant dû', 'my-easy-compta') . '</div>';
        $html .= '<div style="font-size: 20pt; font-weight: bold; color: ' . $primary_color . '; line-height: 1;">';
        $html .= $this->positionCurrency($this->formatAmount($pre_total), $default_currency_symbol->symbol);
        $html .= '</div>';
        $html .= '</td>';

        $html .= '</tr></table>';

        // Barre couleur primaire sous le header
        $html .= '<table width="100%" cellspacing="0" cellpadding="0"><tr>';
        $html .= '<td style="border: none; height: 4px; background-color: ' . $primary_color . '; padding: 0; line-height: 4px; font-size: 1pt;">&#160;</td>';
        $html .= '</tr></table>';

        // ════════════════════════════════════════
        // BANDE DATES
        // ════════════════════════════════════════
        $html .= '<table width="100%" cellspacing="0" cellpadding="0"><tr>';
        $html .= '<td style="border: none; background-color: #f1f5f9; padding: 16px 20px; border-bottom: 1px solid #e2e8f0;">';
        $html .= '<table width="100%" cellspacing="0" cellpadding="0"><tr>';
        $html .= '<td style="border: none;">';
        $html .= '<span style="font-size: 7.5pt; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.8px;">' . htmlspecialchars($date_show_type) . ' : </span>';
        $html .= '<span style="font-size: 9pt; font-weight: bold; color: #334155;">' . $date_type . '</span>';
        $html .= '</td>';
        $html .= '<td style="border: none; text-align: right;">';
        $html .= '<span style="font-size: 7.5pt; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.8px;">' . htmlspecialchars($payment_type) . ' : </span>';
        $html .= '<span style="font-size: 9pt; font-weight: bold; color: ' . $primary_color . ';">' . $this->formatDate($data->due_date) . '</span>';
        $html .= '</td>';
        $html .= '</tr></table>';
        $html .= '</td>';
        $html .= '</tr></table>';

        // ════════════════════════════════════════
        // SECTION PARTIES — Émetteur | Destinataire
        // ════════════════════════════════════════
        $html .= '<table width="100%" cellspacing="0" cellpadding="0" style="margin-top: 36px; margin-bottom: 36px;">';
        $html .= '<tr>';

        // Gauche : DE
        $html .= '<td width="48%" valign="top" style="border: none; padding-right: 24px;">';
        $html .= '<div style="font-size: 6.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1.2px; color: #94a3b8; margin-bottom: 14px;">' . __('Émetteur', 'my-easy-compta') . '</div>';
        $html .= '<div style="font-size: 10pt; font-weight: bold; color: #0f172a; margin-bottom: 14px;">' . htmlspecialchars($this->company_name) . '</div>';
        $html .= '<div style="font-size: 8.5pt; color: #64748b; line-height: 2.6;">';
        if ($this->logo_mentions_active == 1 && !empty($this->logo_mentions)) {
            $html .= nl2br(htmlspecialchars($this->logo_mentions));
        } else {
            $html .= htmlspecialchars($this->company_address) . '<br/>';
            $html .= htmlspecialchars($this->postal_code) . ' ' . htmlspecialchars($this->city);
            if ($this->country)                                 $html .= ', ' . htmlspecialchars($this->country);
            if ($this->company_phone && $this->show_phone == 1) $html .= '<br/>Tél : '    . htmlspecialchars($this->company_phone);
            if ($this->siret)                                   $html .= '<br/>SIRET : '  . htmlspecialchars($this->siret);
            if ($this->tax_number)                              $html .= '<br/>N° TVA : ' . htmlspecialchars($this->tax_number);
        }
        $html .= '</div>';
        $html .= '</td>';

        // Droite : FACTURÉ À
        $html .= '<td width="52%" valign="top" style="border: none; border-left: 1px solid #e2e8f0; padding-left: 32px;">';
        $html .= '<div style="font-size: 6.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1.2px; color: ' . $primary_color . '; margin-bottom: 14px;">' . __('Facturé à', 'my-easy-compta') . '</div>';
        $html .= '<div style="font-size: 11pt; font-weight: bold; color: #0f172a; margin-bottom: 14px;">' . htmlspecialchars($client->company_name) . '</div>';
        $html .= '<div style="font-size: 8.5pt; color: #475569; line-height: 2.6;">';
        if ($client->address)                                       $html .= htmlspecialchars($client->address) . '<br/>';
        $html .= htmlspecialchars($client->postal_code) . ' ' . htmlspecialchars($client->city);
        if ($client->country)                                       $html .= ', ' . htmlspecialchars($client->country);
        if ($client->phone      && $this->show_phone      == 1)     $html .= '<br/>Tél : '    . htmlspecialchars($client->phone);
        if ($client->email      && $this->show_email      == 1)     $html .= '<br/>Email : '  . htmlspecialchars($client->email);
        if ($client->siren_number && $this->show_siren    == 1)     $html .= '<br/>SIREN : '  . htmlspecialchars($client->siren_number);
        if ($client->tax_number && $this->show_tax_number == 1)     $html .= '<br/>N° TVA : ' . htmlspecialchars($client->tax_number);
        $html .= '</div>';
        $html .= '</td>';

        $html .= '</tr></table>';

        // ════════════════════════════════════════
        // TABLEAU DES ARTICLES
        // ════════════════════════════════════════
        $th_s = 'border: none; border-bottom: 2px solid ' . $primary_color . '; padding: 12px 14px; font-size: 7.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.6px; color: #64748b; background-color: #f8fafc;';
        $td0  = 'padding: 18px 14px; font-size: 9pt; vertical-align: top; border: none; border-bottom: 1px solid #f0f4f8; background-color: #ffffff;';

        $html .= '<table width="100%" cellspacing="0" cellpadding="0">';
        $html .= '<thead><tr>';
        $html .= '<th style="' . $th_s . ' text-align: left;" width="' . $w_item . '">'   . __('Désignation', 'my-easy-compta')  . '</th>';
        $html .= '<th style="' . $th_s . ' text-align: center;" width="' . $w_qty . '">'   . __('Qté', 'my-easy-compta')          . '</th>';
        $html .= '<th style="' . $th_s . ' text-align: right;" width="' . $w_price . '">'  . __('Prix unitaire', 'my-easy-compta') . '</th>';
        if ($has_vat)      $html .= '<th style="' . $th_s . ' text-align: right;" width="' . $w_vat . '">'  . __('TVA', 'my-easy-compta')       . '</th>';
        if ($has_discount) $html .= '<th style="' . $th_s . ' text-align: right;" width="' . $w_disc . '">' . __('Remise', 'my-easy-compta')    . '</th>';
        $html .= '<th style="' . $th_s . ' text-align: right;" width="' . $w_total . '">'  . __('Total', 'my-easy-compta') . '</th>';
        $html .= '</tr></thead><tbody>';

        $tva_totaux = [];
        $row_idx    = 0;

        foreach ($items as $item) {
            if ($type === 'invoice' || $type === 'credit_invoice') {
                $quantity            = $encrypt->decrypt($item->quantity);
                $unit_price          = floatval($encrypt->decrypt($item->unit_price));
                $discount_percentage = intval($encrypt->decrypt($item->discount));
                $vat_rate            = intval($encrypt->decrypt($item->vat_rate));
                $item_ref            = $encrypt->decrypt($item->item_ref);
                $item_name           = $encrypt->decrypt($item->item_name);
                $item_description    = $encrypt->decrypt($item->item_description);
            } else {
                $quantity            = $item->quantity;
                $unit_price          = floatval($item->unit_price);
                $discount_percentage = intval($item->discount);
                $vat_rate            = intval($item->vat_rate);
                $item_ref            = $item->item_ref;
                $item_name           = $item->item_name;
                $item_description    = $item->item_description;
            }

            $item_total = $quantity * $unit_price;

            if ($has_vat) {
                $discount_amount               = ($item_total * $discount_percentage) / 100;
                $item_total_after_discount     = $item_total - $discount_amount;
                $item_total_vat                = ($item_total_after_discount * $vat_rate) / 100;
                $total_after_discount_with_vat = $item_total_after_discount + $item_total_vat;
                $sub_total_discounted_with_vat += $total_after_discount_with_vat;
                if (!isset($tva_totaux[$vat_rate])) $tva_totaux[$vat_rate] = 0;
                $tva_totaux[$vat_rate] += $item_total_vat;
            } else {
                $item_total_vat                = 0;
                $discount_amount               = ($item_total * $discount_percentage) / 100;
                $total_after_discount_with_vat = $item_total - $discount_amount;
                $sub_total_discounted_with_vat += $total_after_discount_with_vat;
            }

            $discount_amount      = ($item_total * $discount_percentage) / 100;
            $total_after_discount = $item_total - $discount_amount;
            $sub_total            += $item_total;
            $sub_total_discounted += $total_after_discount;

            $row_idx++;

            $html .= '<tr>';
            $html .= '<td style="' . $td0 . '">';
            $html .= '<div style="font-weight: bold; color: #0f172a; font-size: 9pt;">' . nl2br(htmlspecialchars($item_name)) . '</div>';
            if ($item_ref)
                $html .= '<div style="font-size: 7.5pt; color: #94a3b8; margin-top: 3px;">Ref : ' . nl2br(htmlspecialchars($item_ref)) . '</div>';
            if ($item_description)
                $html .= '<div style="font-size: 8pt; color: #64748b; margin-top: 4px; line-height: 1.5;">' . nl2br(htmlspecialchars($item_description)) . '</div>';
            $html .= '</td>';
            $html .= '<td style="' . $td0 . ' text-align: center; color: #334155;">' . htmlspecialchars((string) $quantity) . '</td>';
            $html .= '<td style="' . $td0 . ' text-align: right; color: #334155;">' . $this->positionCurrency($this->formatAmount($unit_price), $default_currency_symbol->symbol) . '</td>';
            if ($has_vat) {
                $html .= '<td style="' . $td0 . ' text-align: right; color: #334155;">'
                    . $this->positionCurrency($this->formatAmount($item_total_vat), $default_currency_symbol->symbol)
                    . '<br/><span style="font-size: 7pt; color: #94a3b8;">(' . $vat_rate . '%)</span></td>';
            }
            if ($has_discount) {
                $html .= '<td style="' . $td0 . ' text-align: right; color: #ef4444;">'
                    . ($discount_percentage > 0
                        ? '-' . $this->positionCurrency($this->formatAmount($discount_amount), $default_currency_symbol->symbol)
                          . '<br/><span style="font-size: 7pt; color: #94a3b8;">(' . $discount_percentage . '%)</span>'
                        : '—')
                    . '</td>';
            }
            $html .= '<td style="' . $td0 . ' text-align: right; font-weight: bold; color: #0f172a;">'
                . $this->positionCurrency($this->formatAmount($total_after_discount_with_vat), $default_currency_symbol->symbol)
                . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        // ════════════════════════════════════════
        // BAS DE PAGE — Conditions | Récapitulatif
        // ════════════════════════════════════════
        $balance_due = $sub_total_discounted_with_vat;

        $html .= '<table width="100%" cellspacing="0" cellpadding="0" style="margin-top: 40px;">';
        $html .= '<tr>';

        // Gauche : conditions / RIB
        $html .= '<td width="50%" valign="top" style="border: none; padding-right: 28px;">';
        $has_conditions = $invoice_pyament_conditions || $invoice_pyament_mode || $invoice_iban || $terms;
        if ($has_conditions) {
            $html .= '<div style="font-size: 6.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: ' . $primary_color . '; margin-bottom: 14px;">' . __('Conditions générales', 'my-easy-compta') . '</div>';
            if ($invoice_pyament_conditions)
                $html .= '<div style="font-size: 8.5pt; color: #475569; margin-bottom: 12px; line-height: 2.0;"><strong>' . __('Conditions de paiement', 'my-easy-compta') . ' :</strong> ' . htmlspecialchars($invoice_pyament_conditions) . '</div>';
            if ($invoice_pyament_mode)
                $html .= '<div style="font-size: 8.5pt; color: #475569; margin-bottom: 12px; line-height: 2.0;"><strong>' . __('Mode de paiement', 'my-easy-compta') . ' :</strong> ' . htmlspecialchars($invoice_pyament_mode) . '</div>';
            if ($invoice_iban) {
                $html .= '<div style="font-size: 8.5pt; color: #475569; margin-bottom: 12px; line-height: 2.0;"><strong>IBAN :</strong> ' . htmlspecialchars($invoice_iban) . '</div>';
                if ($invoice_bic)
                    $html .= '<div style="font-size: 8.5pt; color: #475569; margin-bottom: 12px; line-height: 2.0;"><strong>BIC :</strong> ' . htmlspecialchars($invoice_bic) . '</div>';
            }
            if ($terms)
                $html .= '<div style="font-size: 7.5pt; color: #64748b; line-height: 2.5; margin-top: 18px;">' . $terms . '</div>';
        }
        $html .= '</td>';

        // Droite : récapitulatif
        $html .= '<td width="50%" valign="top" style="border: none;">';
        $html .= '<table width="100%" cellspacing="0" cellpadding="0">';
        $sr = 'border: none; border-bottom: 1px solid #f0f4f8; padding: 12px 14px;';

        $html .= '<tr>';
        $html .= '<td style="' . $sr . ' color: #64748b; font-size: 9pt;">' . __('Subtotal', 'my-easy-compta') . ' HT</td>';
        $html .= '<td style="' . $sr . ' font-weight: bold; text-align: right; font-size: 9pt; color: #334155;">'
            . $this->positionCurrency($this->formatAmount($sub_total), $default_currency_symbol->symbol) . '</td>';
        $html .= '</tr>';

        if ($has_discount) {
            $html .= '<tr>';
            $html .= '<td style="' . $sr . ' color: #64748b; font-size: 9pt;">' . __('Discount', 'my-easy-compta') . '</td>';
            $html .= '<td style="' . $sr . ' font-weight: bold; text-align: right; font-size: 9pt; color: #ef4444;">- '
                . $this->positionCurrency($this->formatAmount($sub_total - $sub_total_discounted), $default_currency_symbol->symbol) . '</td>';
            $html .= '</tr>';
        }

        if ($has_vat) {
            foreach ($tva_totaux as $rate => $amount) {
                $html .= '<tr>';
                $html .= '<td style="' . $sr . ' color: #64748b; font-size: 9pt;">TVA ' . $rate . '%</td>';
                $html .= '<td style="' . $sr . ' text-align: right; font-size: 9pt; color: #334155;">'
                    . $this->positionCurrency($this->formatAmount($amount), $default_currency_symbol->symbol) . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '<tr>';
        $html .= '<td style="border: none; background-color: ' . $primary_color . '; padding: 14px; font-size: 10pt; font-weight: bold; color: #ffffff; text-transform: uppercase; letter-spacing: 0.5px;">Total TTC</td>';
        $html .= '<td style="border: none; background-color: ' . $primary_color . '; padding: 14px; font-size: 15pt; font-weight: bold; color: #ffffff; text-align: right;">'
            . $this->positionCurrency($this->formatAmount($balance_due), $default_currency_symbol->symbol) . '</td>';
        $html .= '</tr>';

        $html .= '</table>';
        $html .= '</td>';
        $html .= '</tr></table>';

        // Signature pour les devis
        if ($type === 'quote') {
            $file_path = '';
            if ($this->signature_active == 1 && !empty($data->signed) && $data->signed == 1 && !empty($data->file_sign)) {
                $upload_dir = wp_upload_dir();
                $file_path  = $upload_dir['basedir'] . '/signatures/' . $data->file_sign;
            }
            $html .= '<table width="100%" cellspacing="0" cellpadding="0" style="margin-top: 40px;"><tr>';
            $html .= '<td width="55%" style="border: none;"></td>';
            $html .= '<td width="45%" style="border: 1px solid #e2e8f0; padding: 16px 16px 50px;">';
            $html .= '<div style="font-size: 8.5pt; color: #64748b; margin-bottom: 10px;">' . __('Agreement &amp; signature', 'my-easy-compta') . '</div>';
            if ($file_path && file_exists($file_path))
                $html .= '<img style="max-width: 100%; max-height: 60px;" src="' . htmlspecialchars($file_path) . '" />';
            $html .= '</td></tr></table>';
        }

        // ── FOOTER ──
        $html .= '<htmlpagefooter name="myfooter">';
        $html .= '<table width="100%" cellspacing="0" cellpadding="0"><tr>';
        $html .= '<td style="border: none; border-top: 1px solid #e2e8f0; padding-top: 7px; text-align: center; font-size: 7.5pt; color: #94a3b8;">' . $footer . '</td>';
        $html .= '</tr></table></htmlpagefooter>';
        $html .= '<sethtmlpagefooter name="myfooter" value="on" />';

        $html .= '</body></html>';
        return $html;
    }

    private function formatDate($date)
    {
        return wp_date($this->date_format, strtotime($date));
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
