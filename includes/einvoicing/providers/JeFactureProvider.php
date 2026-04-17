<?php

namespace ECWP\EInvoicing\Providers;

use ECWP\EInvoicing\Workflow\PDPInterface;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class JeFactureProvider
 *
 * Connecteur built-in pour JeFacture.com.
 * JeFacture est un PDP (Plateforme de Dématérialisation Partenaire) agréé
 * par la DGFiP pour la transmission des factures électroniques B2B.
 */
class JeFactureProvider implements PDPInterface
{
    private array $config;

    const API_SANDBOX_BASE = 'https://sandbox.jefacture.com/api/v2';
    const API_PROD_BASE    = 'https://api.jefacture.com/api/v2';

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getName(): string
    {
        return 'JeFacture';
    }

    public function getId(): string
    {
        return 'jefacture';
    }

    public function isReady(): bool
    {
        return !empty($this->config['api_key']);
    }

    private function getBaseUrl(): string
    {
        return ($this->config['environment'] ?? 'sandbox') === 'production'
            ? self::API_PROD_BASE
            : self::API_SANDBOX_BASE;
    }

    private function getAuthHeaders(): array
    {
        $headers = [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
            'X-Api-Key'    => $this->config['api_key'],
        ];

        if (!empty($this->config['api_secret'])) {
            $headers['X-Api-Secret'] = $this->config['api_secret'];
        }

        return $headers;
    }

    /**
     * Teste la connexion à l'API JeFacture.
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(): array
    {
        if (!$this->isReady()) {
            return [
                'success' => false,
                'message' => __('Clé API JeFacture manquante.', 'my-easy-compta'),
            ];
        }

        $response = wp_remote_get($this->getBaseUrl() . '/ping', [
            'timeout' => 15,
            'headers' => $this->getAuthHeaders(),
        ]);

        if (is_wp_error($response)) {
            // Fallback : essayer l'endpoint /health
            $response = wp_remote_get($this->getBaseUrl() . '/health', [
                'timeout' => 15,
                'headers' => $this->getAuthHeaders(),
            ]);

            if (is_wp_error($response)) {
                return [
                    'success' => false,
                    'message' => sprintf(
                        __('Connexion impossible à JeFacture : %s', 'my-easy-compta'),
                        $response->get_error_message()
                    ),
                ];
            }
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code === 200) {
            return [
                'success' => true,
                'message' => __('Connexion réussie à JeFacture.', 'my-easy-compta'),
            ];
        }

        if ($code === 401) {
            return [
                'success' => false,
                'message' => __('Clé API invalide (HTTP 401). Vérifiez votre clé JeFacture.', 'my-easy-compta'),
            ];
        }

        if ($code === 403) {
            return [
                'success' => false,
                'message' => __('Accès refusé par JeFacture (HTTP 403). Abonnement actif requis.', 'my-easy-compta'),
            ];
        }

        // Si l'endpoint est joignable, la clé est peut-être valide
        if ($code >= 200 && $code < 300) {
            return ['success' => true, 'message' => __('Connexion réussie à JeFacture.', 'my-easy-compta')];
        }

        return [
            'success' => false,
            'message' => sprintf(__('Réponse JeFacture inattendue (HTTP %d).', 'my-easy-compta'), $code),
        ];
    }

    public function transmit(int $invoiceId, string $xmlContent, string $pdfPath): array
    {
        if (!$this->isReady()) {
            return ['success' => false, 'message' => 'Provider JeFacture non configuré', 'transmission_id' => ''];
        }

        $payload = [
            'invoice_id'  => $invoiceId,
            'facturx_xml' => base64_encode($xmlContent),
            'pdf'         => file_exists($pdfPath) ? base64_encode(file_get_contents($pdfPath)) : '',
            'format'      => 'facturx',
        ];

        $response = wp_remote_post($this->getBaseUrl() . '/invoices/submit', [
            'timeout' => 30,
            'headers' => $this->getAuthHeaders(),
            'body'    => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'message' => $response->get_error_message(), 'transmission_id' => ''];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code === 200 || $code === 201) {
            $transmission_id = $body['transmission_id'] ?? $body['id'] ?? uniqid('jefacture_');
            return ['success' => true, 'message' => 'Facture transmise à JeFacture', 'transmission_id' => (string) $transmission_id];
        }

        return ['success' => false, 'message' => 'Erreur JeFacture (HTTP ' . $code . ')', 'transmission_id' => ''];
    }

    public function getStatus(string $transmissionId): array
    {
        $response = wp_remote_get($this->getBaseUrl() . '/transmissions/' . $transmissionId, [
            'timeout' => 15,
            'headers' => $this->getAuthHeaders(),
        ]);

        if (is_wp_error($response)) {
            return ['status' => 'error', 'details' => $response->get_error_message()];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return [
            'status'  => $body['status'] ?? 'unknown',
            'details' => $body['message'] ?? '',
        ];
    }
}
