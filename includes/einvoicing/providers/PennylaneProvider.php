<?php

namespace ECWP\EInvoicing\Providers;

use ECWP\EInvoicing\Workflow\PDPInterface;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class PennylaneProvider
 *
 * Connecteur built-in pour Pennylane.
 * Pennylane est un PDP agréé permettant la transmission de factures électroniques
 * et la synchronisation comptable.
 */
class PennylaneProvider implements PDPInterface
{
    private array $config;

    const API_BASE_URL = 'https://app.pennylane.com/api/external/v1';

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getName(): string
    {
        return 'Pennylane';
    }

    public function getId(): string
    {
        return 'pennylane';
    }

    public function isReady(): bool
    {
        return !empty($this->config['api_key']);
    }

    /**
     * Teste la connexion à l'API Pennylane avec la clé API fournie.
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(): array
    {
        if (!$this->isReady()) {
            return [
                'success' => false,
                'message' => __('Clé API Pennylane manquante.', 'my-easy-compta'),
            ];
        }

        $response = wp_remote_get(self::API_BASE_URL . '/companies', [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => sprintf(
                    __('Connexion impossible : %s', 'my-easy-compta'),
                    $response->get_error_message()
                ),
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code === 200) {
            $company_name = $body[0]['name'] ?? $body['name'] ?? null;
            $msg = $company_name
                ? sprintf(__('Connecté à Pennylane — Société : %s', 'my-easy-compta'), $company_name)
                : __('Connexion réussie à Pennylane.', 'my-easy-compta');
            return ['success' => true, 'message' => $msg];
        }

        if ($code === 401) {
            return [
                'success' => false,
                'message' => __('Clé API invalide ou expirée (HTTP 401).', 'my-easy-compta'),
            ];
        }

        if ($code === 403) {
            return [
                'success' => false,
                'message' => __('Accès refusé. Vérifiez les permissions de votre clé API (HTTP 403).', 'my-easy-compta'),
            ];
        }

        return [
            'success' => false,
            'message' => sprintf(__('Réponse inattendue de Pennylane (HTTP %d).', 'my-easy-compta'), $code),
        ];
    }

    public function transmit(int $invoiceId, string $xmlContent, string $pdfPath): array
    {
        if (!$this->isReady()) {
            return ['success' => false, 'message' => 'Provider Pennylane non configuré', 'transmission_id' => ''];
        }

        // Pennylane accepte les factures fournisseurs via l'API supplier_invoices
        $pdf_content = file_exists($pdfPath) ? base64_encode(file_get_contents($pdfPath)) : '';

        $payload = [
            'supplier_invoice' => [
                'facturx_xml'  => base64_encode($xmlContent),
                'pdf'          => $pdf_content,
                'pdf_filename' => 'facture_' . $invoiceId . '.pdf',
            ],
        ];

        $response = wp_remote_post(self::API_BASE_URL . '/supplier_invoices', [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'body' => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'message' => $response->get_error_message(), 'transmission_id' => ''];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code === 200 || $code === 201) {
            $transmission_id = $body['id'] ?? $body['supplier_invoice']['id'] ?? uniqid('pennylane_');
            return ['success' => true, 'message' => 'Facture transmise à Pennylane', 'transmission_id' => (string) $transmission_id];
        }

        return ['success' => false, 'message' => 'Erreur Pennylane (HTTP ' . $code . ')', 'transmission_id' => ''];
    }

    public function getStatus(string $transmissionId): array
    {
        $response = wp_remote_get(self::API_BASE_URL . '/supplier_invoices/' . $transmissionId, [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'Accept'        => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return ['status' => 'error', 'details' => $response->get_error_message()];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return [
            'status'  => $body['status'] ?? 'unknown',
            'details' => $body['status_details'] ?? '',
        ];
    }
}
