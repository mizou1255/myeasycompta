<?php

namespace ECWP\EInvoicing\Providers;

use ECWP\EInvoicing\Workflow\PDPInterface;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class GenericProvider
 *
 * Connecteur générique pour tout PDP disposant d'une API REST.
 * Permet de connecter n'importe quel PDP agréé via une URL d'API,
 * une clé API et un secret optionnel.
 */
class GenericProvider implements PDPInterface
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getName(): string
    {
        return !empty($this->config['name']) ? $this->config['name'] : 'PDP Personnalisé';
    }

    public function getId(): string
    {
        return 'generic';
    }

    public function isReady(): bool
    {
        return !empty($this->config['api_endpoint']);
    }

    private function getAuthHeaders(): array
    {
        $headers = ['Accept' => 'application/json'];

        if (!empty($this->config['api_key'])) {
            // Support des deux styles d'authentification courants
            $auth_style = $this->config['auth_style'] ?? 'bearer';
            if ($auth_style === 'basic' && !empty($this->config['api_secret'])) {
                $headers['Authorization'] = 'Basic ' . base64_encode($this->config['api_key'] . ':' . $this->config['api_secret']);
            } elseif ($auth_style === 'apikey') {
                $headers['X-Api-Key'] = $this->config['api_key'];
                if (!empty($this->config['api_secret'])) {
                    $headers['X-Api-Secret'] = $this->config['api_secret'];
                }
            } else {
                // Bearer token par défaut
                $headers['Authorization'] = 'Bearer ' . $this->config['api_key'];
            }
        }

        return $headers;
    }

    /**
     * Teste la connectivité avec l'endpoint API fourni.
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(): array
    {
        if (!$this->isReady()) {
            return [
                'success' => false,
                'message' => __('URL de l\'API PDP manquante.', 'my-easy-compta'),
            ];
        }

        $endpoint = esc_url_raw(trailingslashit($this->config['api_endpoint']));

        // Essai sur l'URL racine / ping / health
        $test_urls = [
            rtrim($endpoint, '/') . '/ping',
            rtrim($endpoint, '/') . '/health',
            rtrim($endpoint, '/'),
        ];

        foreach ($test_urls as $url) {
            $response = wp_remote_get($url, [
                'timeout' => 10,
                'headers' => $this->getAuthHeaders(),
            ]);

            if (is_wp_error($response)) {
                continue;
            }

            $code = wp_remote_retrieve_response_code($response);

            if ($code === 200 || $code === 204) {
                return [
                    'success' => true,
                    'message' => sprintf(__('Connexion réussie à %s (HTTP %d).', 'my-easy-compta'), $this->getName(), $code),
                ];
            }

            if ($code === 401) {
                return [
                    'success' => false,
                    'message' => sprintf(__('API joignable mais authentification refusée (HTTP 401). Vérifiez votre clé API pour %s.', 'my-easy-compta'), $this->getName()),
                ];
            }

            if ($code === 403) {
                return [
                    'success' => false,
                    'message' => sprintf(__('API joignable mais accès refusé (HTTP 403). Vérifiez les permissions pour %s.', 'my-easy-compta'), $this->getName()),
                ];
            }

            if ($code >= 200 && $code < 500) {
                // L'endpoint répond — considéré comme joignable
                return [
                    'success' => ($code < 400),
                    'message' => sprintf(__('API %s joignable (HTTP %d).', 'my-easy-compta'), $this->getName(), $code),
                ];
            }
        }

        return [
            'success' => false,
            'message' => sprintf(__('Impossible de joindre l\'API de %s. Vérifiez l\'URL et votre connexion.', 'my-easy-compta'), $this->getName()),
        ];
    }

    public function transmit(int $invoiceId, string $xmlContent, string $pdfPath): array
    {
        if (!$this->isReady()) {
            return ['success' => false, 'message' => 'Provider générique non configuré', 'transmission_id' => ''];
        }

        $endpoint = rtrim(esc_url_raw($this->config['api_endpoint']), '/') . '/invoices';

        $payload = [
            'invoice_id'  => $invoiceId,
            'facturx_xml' => base64_encode($xmlContent),
            'pdf'         => file_exists($pdfPath) ? base64_encode(file_get_contents($pdfPath)) : '',
        ];

        $headers               = $this->getAuthHeaders();
        $headers['Content-Type'] = 'application/json';

        $response = wp_remote_post($endpoint, [
            'timeout' => 30,
            'headers' => $headers,
            'body'    => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'message' => $response->get_error_message(), 'transmission_id' => ''];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code === 200 || $code === 201) {
            $transmission_id = $body['id'] ?? $body['transmission_id'] ?? uniqid('generic_');
            return ['success' => true, 'message' => 'Facture transmise', 'transmission_id' => (string) $transmission_id];
        }

        return ['success' => false, 'message' => 'Erreur transmission (HTTP ' . $code . ')', 'transmission_id' => ''];
    }

    public function getStatus(string $transmissionId): array
    {
        if (!$this->isReady()) {
            return ['status' => 'error', 'details' => 'Provider non configuré'];
        }

        $endpoint = rtrim(esc_url_raw($this->config['api_endpoint']), '/') . '/transmissions/' . $transmissionId;

        $response = wp_remote_get($endpoint, [
            'timeout' => 15,
            'headers' => $this->getAuthHeaders(),
        ]);

        if (is_wp_error($response)) {
            return ['status' => 'error', 'details' => $response->get_error_message()];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return [
            'status'  => $body['status'] ?? 'unknown',
            'details' => $body['message'] ?? $body['details'] ?? '',
        ];
    }
}
