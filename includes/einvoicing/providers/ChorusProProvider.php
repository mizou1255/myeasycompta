<?php

namespace ECWP\EInvoicing\Providers;

use ECWP\EInvoicing\Workflow\PDPInterface;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ChorusProProvider
 *
 * Connecteur built-in pour Chorus Pro (via l'API PISTE du gouvernement français).
 * Chorus Pro est la plateforme officielle de l'État pour la dématérialisation
 * des factures dans les marchés publics (B2G).
 */
class ChorusProProvider implements PDPInterface
{
    private array $config;

    const PISTE_SANDBOX_TOKEN_URL  = 'https://sandbox-api.piste.gouv.fr/api/oauth/token';
    const PISTE_PROD_TOKEN_URL     = 'https://api.piste.gouv.fr/api/oauth/token';
    const CHORUS_SANDBOX_BASE_URL  = 'https://chorus-pro.gouv.fr/cpp/services/rest';
    const CHORUS_PROD_BASE_URL     = 'https://chorus-pro.gouv.fr/cpp/services/rest';

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getName(): string
    {
        return 'Chorus Pro';
    }

    public function getId(): string
    {
        return 'chorus_pro';
    }

    public function isReady(): bool
    {
        return !empty($this->config['api_key']) && !empty($this->config['api_secret']);
    }

    /**
     * Teste la connexion à l'API PISTE de Chorus Pro.
     * Tente une authentification OAuth2 avec les identifiants fournis.
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(): array
    {
        if (!$this->isReady()) {
            return [
                'success' => false,
                'message' => __('Identifiants manquants (login et mot de passe PISTE requis).', 'my-easy-compta'),
            ];
        }

        $is_sandbox  = ($this->config['environment'] ?? 'sandbox') !== 'production';
        $token_url   = $is_sandbox ? self::PISTE_SANDBOX_TOKEN_URL : self::PISTE_PROD_TOKEN_URL;

        $response = wp_remote_post($token_url, [
            'timeout'    => 15,
            'body'       => [
                'grant_type' => 'password',
                'username'   => $this->config['api_key'],
                'password'   => $this->config['api_secret'],
                'client_id'  => 'chorus-pro-mgpp',
                'realm'      => 'chorus-pro',
            ],
            'headers'    => [
                'Accept' => 'application/json',
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

        if ($code === 200 && !empty($body['access_token'])) {
            return [
                'success' => true,
                'message' => __('Connexion réussie à Chorus Pro (PISTE).', 'my-easy-compta'),
            ];
        }

        if ($code === 401 || $code === 400) {
            $error = $body['error_description'] ?? $body['error'] ?? 'Identifiants invalides';
            return [
                'success' => false,
                'message' => sprintf(__('Authentification échouée : %s', 'my-easy-compta'), $error),
            ];
        }

        // PISTE is reachable even if credentials wrong — endpoint reached
        if ($code >= 400 && $code < 500) {
            return [
                'success' => false,
                'message' => sprintf(__('Erreur d\'authentification (HTTP %d). Vérifiez vos identifiants PISTE.', 'my-easy-compta'), $code),
            ];
        }

        return [
            'success' => false,
            'message' => sprintf(__('Réponse inattendue (HTTP %d).', 'my-easy-compta'), $code),
        ];
    }

    public function transmit(int $invoiceId, string $xmlContent, string $pdfPath): array
    {
        if (!$this->isReady()) {
            return ['success' => false, 'message' => 'Provider non configuré', 'transmission_id' => ''];
        }

        // Obtenir le token d'accès
        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'message' => 'Impossible d\'obtenir un token PISTE', 'transmission_id' => ''];
        }

        $is_sandbox = ($this->config['environment'] ?? 'sandbox') !== 'production';
        $base_url   = $is_sandbox ? self::CHORUS_SANDBOX_BASE_URL : self::CHORUS_PROD_BASE_URL;

        // Préparer le payload multipart (PDF + XML)
        $boundary = wp_generate_password(24, false);
        $body     = '--' . $boundary . "\r\n";
        $body    .= 'Content-Disposition: form-data; name="fichierPDF"; filename="facture.pdf"' . "\r\n";
        $body    .= 'Content-Type: application/pdf' . "\r\n\r\n";
        $body    .= (file_exists($pdfPath) ? file_get_contents($pdfPath) : '') . "\r\n";
        $body    .= '--' . $boundary . "\r\n";
        $body    .= 'Content-Disposition: form-data; name="fichierFx"; filename="facturex.xml"' . "\r\n";
        $body    .= 'Content-Type: application/xml' . "\r\n\r\n";
        $body    .= $xmlContent . "\r\n";
        $body    .= '--' . $boundary . '--';

        $response = wp_remote_post($base_url . '/v1/factures/deposer', [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
            ],
            'body'    => $body,
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'message' => $response->get_error_message(), 'transmission_id' => ''];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code === 200 || $code === 201) {
            $transmission_id = $body['identifiantFactureCPP'] ?? uniqid('chorus_');
            return ['success' => true, 'message' => 'Facture transmise à Chorus Pro', 'transmission_id' => (string) $transmission_id];
        }

        return ['success' => false, 'message' => 'Erreur Chorus Pro (HTTP ' . $code . ')', 'transmission_id' => ''];
    }

    public function getStatus(string $transmissionId): array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return ['status' => 'error', 'details' => 'Token PISTE indisponible'];
        }

        $is_sandbox = ($this->config['environment'] ?? 'sandbox') !== 'production';
        $base_url   = $is_sandbox ? self::CHORUS_SANDBOX_BASE_URL : self::CHORUS_PROD_BASE_URL;

        $response = wp_remote_get($base_url . '/v1/factures/' . $transmissionId . '/statut', [
            'timeout' => 15,
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        if (is_wp_error($response)) {
            return ['status' => 'error', 'details' => $response->get_error_message()];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return [
            'status'  => $body['statutCourantCode'] ?? 'unknown',
            'details' => $body['statutCourantLibelle'] ?? '',
        ];
    }

    private function getAccessToken(): ?string
    {
        $is_sandbox = ($this->config['environment'] ?? 'sandbox') !== 'production';
        $token_url  = $is_sandbox ? self::PISTE_SANDBOX_TOKEN_URL : self::PISTE_PROD_TOKEN_URL;

        $response = wp_remote_post($token_url, [
            'timeout' => 15,
            'body'    => [
                'grant_type' => 'password',
                'username'   => $this->config['api_key'],
                'password'   => $this->config['api_secret'],
                'client_id'  => 'chorus-pro-mgpp',
                'realm'      => 'chorus-pro',
            ],
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['access_token'] ?? null;
    }
}
