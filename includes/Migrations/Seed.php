<?php

namespace ECWP\Admin;

class ECWP_Tables
{
    public function create_tables()
    {
        $table_files = [
            'settings.php',
            'vats.php',
            'currency.php',
            'articles.php',
            'clients.php',
            'quotes.php',
            'quotes-items.php',
            'invoices.php',
            'invoices-items.php',
            'payments.php',
            'payments-methods.php',
            'expenses.php',
            'expenses-attachments.php',
            'expenses-categories.php',
        ];

        foreach ($table_files as $file) {
            require_once plugin_dir_path(__FILE__) . 'SQL/' . $file;
        }
    }

    public function drop_tables()
    {
        global $wpdb;
        $tables = [
            ECWP_TABLE_SETTINGS,
            ECWP_TABLE_ARTICLES,
            ECWP_TABLE_ARTICLES_CATEGORIES,
            ECWP_TABLE_CLIENTS,
            ECWP_TABLE_INVOICES,
            ECWP_TABLE_INVOICE_ELEMENTS,
            ECWP_TABLE_QUOTES,
            ECWP_TABLE_QUOTE_ELEMENTS,
            ECWP_TABLE_PAYMENTS,
            ECWP_TABLE_PAYMENTS_METHODS,
            ECWP_TABLE_EXPENSES,
            ECWP_TABLE_EXPENSES_CATEGORIES,
            ECWP_TABLE_EXPENSES_ATTACHMENTS,
            ECWP_TABLE_CURRENCY,
            ECWP_TABLE_VATS,
        ];

        foreach ($tables as $table) {
            // Ensure table name is safely sanitized and validated
            $table = sanitize_key($table);

            $wpdb->query("DROP TABLE IF EXISTS %i", $table);
        }
    }

    public function import_data_settings(array $params)
    {
        global $wpdb;

        $logo_url = ECWP_ASSETS . '/img/logo.png';
        $logo_path = ECWP_PATH . '/assets/img/logo.png';

        $settings = [
            'company_code' => isset($params['company_code']) ? $params['company_code'] : '',
            'tax_number' => isset($params['tax_number']) ? $params['tax_number'] : '',
            'company_name' => isset($params['company_name']) ? $params['company_name'] : '',
            'company_address' => isset($params['company_address']) ? $params['company_address'] : '',
            'city' => isset($params['city']) ? $params['city'] : '',
            'country' => isset($params['country']) ? $params['country'] : '',
            'postal_code' => isset($params['postal_code']) ? $params['postal_code'] : '',
            'company_email' => isset($params['company_email']) ? $params['company_email'] : '',
            'company_phone' => isset($params['company_phone']) ? $params['company_phone'] : '',
            'mobile_phone' => isset($params['mobile_phone']) ? $params['mobile_phone'] : '',
            'fax' => isset($params['fax']) ? $params['fax'] : '',
            'default_currency' => isset($params['default_currency']) ? $params['default_currency'] : 2,
            'vat_active' => isset($params['vat_active']) ? $params['vat_active'] : 0,
            'default_vat' => isset($params['default_vat']) ? $params['default_vat'] : '',
            'quote_prefix' => isset($params['quote_prefix']) ? $params['quote_prefix'] : 'EST',
            'invoice_prefix' => isset($params['invoice_prefix']) ? $params['invoice_prefix'] : 'INV',
            'quote_first' => isset($params['quote_first']) ? $params['quote_first'] : 1,
            'invoice_first' => isset($params['invoice_first']) ? $params['invoice_first'] : 1,
        ];

        foreach ($settings as $key => $value) {
            $wpdb->replace(
                ECWP_TABLE_SETTINGS,
                [
                    'meta_key' => $key,
                    'meta_value' => $value,
                ],
                [
                    '%s',
                    '%s',
                ]
            );
        }

        $settings_data = array(
            array('meta_key' => 'logo_url', 'meta_value' => $logo_url),
            array('meta_key' => 'date_format', 'meta_value' => 'DD/MM/YYYY'),
            array('meta_key' => 'logo_width', 'meta_value' => '99'),
            array('meta_key' => 'invoice_color', 'meta_value' => '#0860d6'),
            array('meta_key' => 'invoice_footer', 'meta_value' => '<p>Dispensé d\'immatriculation au registre du commerce et des sociétés (RCS) et au répertoire des métiers (RM)</p>'),
            array('meta_key' => 'invoice_terms', 'meta_value' => '<p><strong>TVA non applicable</strong>, art. 293 B du CGI Pénalité de retard au taux annuel de 2% En cas de retard de paiement, application d\'une indemnité forfaitaire pour frais de recouvrement de 40 euros (article D. 441-5 du code du commerce)</p>'),
            array('meta_key' => 'quote_color', 'meta_value' => '#29c742'),
            array('meta_key' => 'quote_footer', 'meta_value' => '<p>Dispensé d\'immatriculation au registre du commerce et des sociétés (RCS) et au répertoire des métiers (RM)</p>'),
            array('meta_key' => 'quote_terms', 'meta_value' => '<p><strong>TVA non applicable</strong>, art. 293 B du CGI Pénalité de retard au taux annuel de 2% En cas de retard de paiement, application d\'une indemnité forfaitaire pour frais de recouvrement de 40 euros (article D. 441-5 du code du commerce)</p>'),
            array('meta_key' => 'logo_path', 'meta_value' => $logo_path),
            array('meta_key' => 'currency_position', 'meta_value' => 'after'),
        );

        $articles_categories = array(
            array('name' => 'Livraison de biens'),
            array('name' => 'Prestation de service'),
            array('name' => 'Mixte'),
        );

        foreach ($articles_categories as $category) {
            $wpdb->insert(ECWP_TABLE_ARTICLES_CATEGORIES, $category);
        }

        foreach ($settings_data as $data) {
            $wpdb->insert(ECWP_TABLE_SETTINGS, $data);
        }

        $currency_data = array(
            array('code' => 'USD', 'symbol' => '$', 'name' => 'US Dollar'),
            array('code' => 'EUR', 'symbol' => '€', 'name' => 'Euro'),
            array('code' => 'GBP', 'symbol' => '£', 'name' => 'British Pound'),
            array('code' => 'JPY', 'symbol' => '¥', 'name' => 'Japanese Yen'),
            array('code' => 'AUD', 'symbol' => 'A$', 'name' => 'Australian Dollar'),
            array('code' => 'CAD', 'symbol' => 'C$', 'name' => 'Canadian Dollar'),
            array('code' => 'CHF', 'symbol' => 'CHF', 'name' => 'Swiss Franc'),
            array('code' => 'CNY', 'symbol' => '¥', 'name' => 'Chinese Yuan'),
            array('code' => 'SEK', 'symbol' => 'kr', 'name' => 'Swedish Krona'),
            array('code' => 'NZD', 'symbol' => 'NZ$', 'name' => 'New Zealand Dollar'),
        );

        foreach ($currency_data as $data) {
            $wpdb->insert(ECWP_TABLE_CURRENCY, $data);
        }

        $expenses_categories_data = array(
            array('name' => 'Office Supplies'),
            array('name' => 'Travel Expenses'),
            array('name' => 'IT Equipment'),
            array('name' => 'Marketing'),
            array('name' => 'Utilities'),
            array('name' => 'Legal Fees'),
            array('name' => 'Employee Salaries'),
            array('name' => 'Insurance'),
            array('name' => 'Rent'),
            array('name' => 'Training and Development'),
        );

        foreach ($expenses_categories_data as $category) {
            $wpdb->insert(ECWP_TABLE_EXPENSES_CATEGORIES, $category);
        }

        $payment_methods_data = array(
            array('method_name' => 'Credit Card'),
            array('method_name' => 'PayPal'),
            array('method_name' => 'Bank Transfer'),
            array('method_name' => 'Cash'),
            array('method_name' => 'Cheque'),
            array('method_name' => 'Cryptocurrency'),
        );

        foreach ($payment_methods_data as $method) {
            $wpdb->insert(ECWP_TABLE_PAYMENTS_METHODS, $method);
        }

        $vat_data = array(
            array('rate' => 20, 'description' => 'Standard VAT Rate'),
            array('rate' => 15, 'description' => 'Reduced VAT Rate'),
            array('rate' => 10, 'description' => 'Intermediate VAT Rate'),
            array('rate' => 5, 'description' => 'Super Reduced VAT Rate'),
        );

        foreach ($vat_data as $vat) {
            $wpdb->insert(ECWP_TABLE_VATS, $vat);
        }
    }

    public function import_demo_data()
    {
        global $wpdb;

        // Insert new clients
        $clients = [
            ['company_name' => 'Tesla', 'manager_name' => 'Elon Musk', 'address' => '3500 Deer Creek Road', 'city' => 'Palo Alto', 'postal_code' => '94304', 'country' => 'USA', 'phone' => '(650) 681-5000', 'mobile_phone' => '(650) 681-5001', 'email' => 'elon.musk@tesla.com', 'website' => 'https://www.tesla.com', 'currency_id' => 2, 'note' => ''],
            ['company_name' => 'Amazon', 'manager_name' => 'Jeff Bezos', 'address' => '410 Terry Ave N', 'city' => 'Seattle', 'postal_code' => '98109', 'country' => 'USA', 'phone' => '(206) 266-1000', 'mobile_phone' => '(206) 266-1001', 'email' => 'jeff.bezos@amazon.com', 'website' => 'https://www.amazon.com', 'currency_id' => 2, 'note' => ''],
            ['company_name' => 'Microsoft', 'manager_name' => 'Satya Nadella', 'address' => '1 Microsoft Way', 'city' => 'Redmond', 'postal_code' => '98052', 'country' => 'USA', 'phone' => '(425) 882-8080', 'mobile_phone' => '(425) 882-8081', 'email' => 'satya.nadella@microsoft.com', 'website' => 'https://www.microsoft.com', 'currency_id' => 2, 'note' => ''],
        ];

        foreach ($clients as $client) {
            $wpdb->insert(ECWP_TABLE_CLIENTS, $client);
        }

        // Get client IDs
        $client_id_tesla = $wpdb->get_var($wpdb->prepare("SELECT id FROM %i WHERE company_name = %s", ECWP_TABLE_CLIENTS, 'Tesla'));
        $client_id_amazon = $wpdb->get_var($wpdb->prepare("SELECT id FROM %i WHERE company_name = %s", ECWP_TABLE_CLIENTS, 'Amazon'));
        $client_id_microsoft = $wpdb->get_var($wpdb->prepare("SELECT id FROM %i WHERE company_name = %s", ECWP_TABLE_CLIENTS, 'Microsoft'));

        $invoice_first = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM %i WHERE meta_key = 'invoice_first'", ECWP_TABLE_SETTINGS));
        $invoice_prefix = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM %i WHERE meta_key = 'invoice_prefix'", ECWP_TABLE_SETTINGS));
        $invoice_first = $invoice_first ? intval($invoice_first) : 1;
        $invoice_prefix = $invoice_prefix ? sanitize_text_field($invoice_prefix) : 'INV';
        $invoice_number = $invoice_prefix . '_' . str_pad($invoice_first, 4, '0', STR_PAD_LEFT);

        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        $invoice_number = $encrypt->encrypt($invoice_number);
        // Insert a new invoice
        $wpdb->insert(ECWP_TABLE_INVOICES, [
            'client_id' => $client_id_tesla,
            'number' => $invoice_first,
            'invoice_number' => $invoice_number,
            'amount' => $encrypt->encrypt(1500.00),
            'total_amount' => $encrypt->encrypt(1800.00),
            'exchange_rate' => $encrypt->encrypt(1.3000),
            'status' => $encrypt->encrypt('paid'),
            'due_date' => '2024-07-30',
            'created_at' => '2024-06-14',
            'source' => '',
        ]);

        // Get invoice ID
        $invoice_id_tesla = $wpdb->get_var($wpdb->prepare("SELECT id FROM %i WHERE invoice_number = %s", ECWP_TABLE_INVOICES, $invoice_number));

        // Insert a new invoice item
        $wpdb->insert(ECWP_TABLE_INVOICE_ELEMENTS, [
            'invoice_id' => $invoice_id_tesla,
            'item_name' => $encrypt->encrypt('Model S'),
            'item_ref' => $encrypt->encrypt('ref1'),
            'item_category' => 1,
            'item_description' => 'Electric car',
            'quantity' => $encrypt->encrypt(1),
            'vat_rate' => $encrypt->encrypt(20),
            'unit_price' => $encrypt->encrypt(1500.00),
            'discount' => $encrypt->encrypt(0),
            'total_price' => $encrypt->encrypt(1500.00),
            'total_amount' => $encrypt->encrypt(1800.00),
            'item_order' => 1,
        ]);

        $quote_first = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM %i WHERE meta_key = 'quote_first'", ECWP_TABLE_SETTINGS));
        $quote_prefix = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM %i WHERE meta_key = 'quote_prefix'", ECWP_TABLE_SETTINGS));
        $quote_first = $quote_first ? intval($quote_first) : 1;
        $quote_prefix = $quote_prefix ? sanitize_text_field($quote_prefix) : 'INV';
        $quote_number = $quote_prefix . '_' . str_pad($quote_first, 4, '0', STR_PAD_LEFT);

        // Insert a new quote
        $wpdb->insert(ECWP_TABLE_QUOTES, [
            'client_id' => $client_id_amazon,
            'quote_number' => $quote_number,
            'total_amount' => 2500.00,
            'status' => 'pending',
            'due_date' => '2024-07-15',
            'created_at' => '2024-06-30',
        ]);

        // Get quote ID
        $quote_id_amazon = $wpdb->get_var($wpdb->prepare("SELECT id FROM %i WHERE quote_number = %s", ECWP_TABLE_QUOTES, 'EST_0001'));

        // Insert a new quote element
        $wpdb->insert(ECWP_TABLE_QUOTE_ELEMENTS, [
            'quote_id' => $quote_id_amazon,
            'item_name' => 'AWS Cloud Service',
            'item_ref' => 'ref1',
            'item_category' => 1,
            'item_description' => 'Cloud computing service',
            'quantity' => 10,
            'vat_rate' => 20,
            'unit_price' => 250.00,
            'discount' => 0,
            'total_price' => 2500.00,
            'total_amount' => 2750.00,
            'item_order' => 1,
        ]);

        // Insert a new payment
        $wpdb->insert(ECWP_TABLE_PAYMENTS, [
            'invoice_id' => $invoice_id_tesla,
            'client_id' => $client_id_tesla,
            'amount' => 2500.00,
            'payment_method_id' => 1,
            'payment_date' => '2024-08-14',
            'notes' => 'Payment for invoice INV_0001',
        ]);

        // Insert a new expense
        $wpdb->insert(ECWP_TABLE_EXPENSES, [
            'amount' => 200.00,
            'expense_date' => '2024-06-14',
            'client_id' => $client_id_microsoft,
            'category_id' => 1,
            'attachment_id' => null,
            'notes' => 'Office supplies',
        ]);
    }
}
