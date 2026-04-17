<?php

namespace ECWP\EInvoicing\Model;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class InvoiceModel
 * 
 * Modèle de données strict pour une facture conforme EN16931.
 * 
 * @package ECWP\EInvoicing\Model
 */
class InvoiceModel
{
    /**
     * ID de la facture dans myEasyCompta
     * @var int
     */
    private $id;

    /**
     * Numéro de la facture (BT-1)
     * @var string
     */
    private $number;

    /**
     * Date d'émission (BT-2)
     * @var \DateTime
     */
    private $issueDate;

    /**
     * Date d'échéance
     * @var \DateTime|null
     */
    private $dueDate;

    /**
     * Type de facture (BT-3)
     * 380 = Facture commerciale
     * 381 = Avoir
     * @var string
     */
    private $typeCode;

    /**
     * Devise (BT-5)
     * @var string
     */
    private $currency;

    /**
     * Vendeur (BT-27)
     * @var array
     */
    private $seller;

    /**
     * Acheteur (BT-44)
     * @var array
     */
    private $buyer;

    /**
     * Lignes de facture (BG-25)
     * @var array
     */
    private $lines = [];

    /**
     * Totaux (BG-22)
     * @var array
     */
    private $totals = [];

    /**
     * Statut de la facture
     * @var string
     */
    private $status;

    /**
     * Code moyen de paiement (BT-81) — 30=Virement, 42=Compte bancaire, 58=SEPA
     * @var string
     */
    private $paymentMeansCode = '30';

    public function __construct(int $id)
    {
        $this->id = $id;
        $this->loadFromDb();
    }

    /**
     * Charge les données depuis la base de données myEasyCompta
     * et les mappe vers le modèle EN16931.
     */
    private function loadFromDb()
    {
        global $wpdb;
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();

        // 1. Charger la facture
        $invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ECWP_TABLE_INVOICES . " WHERE id = %d",
            $this->id
        ));

        if (!$invoice) {
            throw new \Exception("Facture introuvable (ID: {$this->id})");
        }

        $this->number    = $encrypt->decrypt($invoice->invoice_number);
        $this->issueDate = new \DateTime($invoice->created_at);
        $this->dueDate   = !empty($invoice->due_date) ? new \DateTime($invoice->due_date) : null;
        // TypeCode 381 = Avoir (credit note), 380 = Facture commerciale
        $this->typeCode  = !empty($invoice->credit) ? '381' : '380';
        $this->status    = $invoice->fiscal_status ?: 'draft';

        // 2. Charger le client (Acheteur)
        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ECWP_TABLE_CLIENTS . " WHERE id = %d",
            $invoice->client_id
        ));

        if (!$client) {
            throw new \Exception("Client introuvable pour la facture {$this->id}");
        }

        // Récupérer la devise depuis le client
        $currency_table = ECWP_TABLE_CURRENCY;
        $currency_code = $wpdb->get_var($wpdb->prepare(
            "SELECT code FROM {$currency_table} WHERE id = %d",
            $client->currency_id
        ));
        $this->currency = $currency_code ?: 'EUR';

        $this->buyer = [
            'name' => $client->company_name,
            'siren' => (string) ($client->siren_number ?? ''),
            'tax_number' => (string) ($client->tax_number ?? ''),
            'vat_number' => (string) ($client->vat_number ?? ''),
            'address' => [
                'line1' => (string) ($client->address ?? ''),
                'city' => (string) ($client->city ?? ''),
                'postal_code' => (string) ($client->postal_code ?? ''),
                'country' => (string) ($client->country ?? 'FR'),
            ],
        ];

        // 3. Charger le vendeur (Settings)
        $settings_table = ECWP_TABLE_SETTINGS;
        $settings_rows = $wpdb->get_results("SELECT meta_key, meta_value FROM {$settings_table}");
        $settings = [];
        foreach ($settings_rows as $row) {
            $settings[$row->meta_key] = $row->meta_value;
        }

        $this->seller = [
            'name' => (string) ($settings['company_name'] ?? ''),
            'siren' => (string) ($settings['company_code'] ?? ''),
            'tax_number' => (string) ($settings['tax_number'] ?? ''),
            'vat_number' => (string) ($settings['vat_number'] ?? ''),
            'address' => [
                'line1' => (string) ($settings['company_address'] ?? ''),
                'city' => (string) ($settings['city'] ?? ''),
                'postal_code' => (string) ($settings['postal_code'] ?? ''),
                'country' => (string) ($settings['country'] ?? 'FR'),
            ],
        ];

        // 4. Charger les lignes
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . ECWP_TABLE_INVOICE_ELEMENTS . " WHERE invoice_id = %d ORDER BY item_order ASC",
            $this->id
        ));

        foreach ($items as $item) {
            $this->lines[] = [
                'name' => $encrypt->decrypt($item->item_name),
                'ref' => $encrypt->decrypt($item->item_ref),
                'description' => $encrypt->decrypt($item->item_description),
                'quantity' => (float) $encrypt->decrypt($item->quantity),
                'unit_code' => $item->unit_code ?? 'C62',
                'classification_code' => $item->classification_code ?? '',
                'unit_price' => (float) $encrypt->decrypt($item->unit_price),
                'vat_rate' => (float) $encrypt->decrypt($item->vat_rate),
                'discount' => (float) $encrypt->decrypt($item->discount),
                'total_price' => (float) $encrypt->decrypt($item->total_price),
                'total_amount' => (float) $encrypt->decrypt($item->total_amount),
            ];
        }

        // 5. Totaux (calculés ou récupérés)
        $this->totals = [
            'amount_excl_tax' => (float) $encrypt->decrypt($invoice->amount),
            'amount_incl_tax' => (float) $encrypt->decrypt($invoice->total_amount),
        ];

        // 6. Moyen de paiement depuis la méthode de paiement enregistrée
        $payment_row = $wpdb->get_row($wpdb->prepare(
            "SELECT pm.facturx_code FROM " . ECWP_TABLE_PAYMENTS . " p
             LEFT JOIN " . ECWP_TABLE_PAYMENTS_METHODS . " pm ON p.payment_method_id = pm.id
             WHERE p.invoice_id = %d
             ORDER BY p.id DESC
             LIMIT 1",
            $this->id
        ));
        // facturx_code column may not exist yet; fallback to '30' (virement)
        if ($payment_row && !empty($payment_row->facturx_code)) {
            $this->paymentMeansCode = (string) $payment_row->facturx_code;
        }
    }

    /**
     * Valide la conformité EN16931
     * 
     * @throws \Exception Si la validation échoue
     * @return bool
     */
    public function validate(): bool
    {
        $errors = [];

        if (empty($this->number)) {
            $errors[] = "Le numéro de facture (BT-1) est obligatoire.";
        }

        if (!$this->issueDate instanceof \DateTime) {
            $errors[] = "La date d'émission (BT-2) est invalide.";
        }

        if (!in_array($this->typeCode, ['380', '381'])) {
            $errors[] = "Le code type de facture (BT-3) doit être 380 ou 381.";
        }

        if (empty($this->seller['name'])) {
            $errors[] = "Le nom du vendeur (BT-27) est obligatoire.";
        }

        // SIREN obligatoire pour la France
        if (empty($this->seller['siren'])) {
            $errors[] = "Le SIREN/SIRET du vendeur (BT-29) est obligatoire.";
        }

        if (empty($this->buyer['name'])) {
            $errors[] = "Le nom de l'acheteur (BT-44) est obligatoire.";
        }

        if (empty($this->buyer['address']['line1']) || empty($this->buyer['address']['postal_code']) || empty($this->buyer['address']['city'])) {
            $errors[] = "L'adresse de l'acheteur (BG-8) est incomplète.";
        }

        if (empty($this->lines)) {
            $errors[] = "La facture doit contenir au moins une ligne.";
        }

        if (!empty($errors)) {
            throw new \Exception("Validation EN16931 échouée : " . implode(", ", $errors));
        }

        return true;
    }

    // Getters
    public function getId(): int
    {
        return $this->id;
    }
    public function getNumber(): string
    {
        return $this->number;
    }
    public function getIssueDate(): \DateTime
    {
        return $this->issueDate;
    }
    public function getDueDate(): ?\DateTime
    {
        return $this->dueDate;
    }
    public function getTypeCode(): string
    {
        return $this->typeCode;
    }
    public function getCurrency(): string
    {
        return $this->currency;
    }
    public function getSeller(): array
    {
        return $this->seller;
    }
    public function getBuyer(): array
    {
        return $this->buyer;
    }
    public function getLines(): array
    {
        return $this->lines;
    }
    public function getTotals(): array
    {
        return $this->totals;
    }
    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPaymentMeansCode(): string
    {
        return $this->paymentMeansCode;
    }
}
