<?php

namespace ECWP\Admin\Settings;

use ECWP\API\Routes;

class ECWP_Settings
{
    protected $routes;

    public function __construct()
    {
        // Plus de sous-menu WordPress - navigation SPA uniquement
        $this->routes = new Routes();
        $this->register_api_routes();
    }

    private function register_api_routes()
    {
        $this->routes->add_route('settings/get', 'GET', $this, 'get_settings', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('settings/save', 'POST', $this, 'save_settings', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('settings/email-preview', 'GET', $this, 'preview_email', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('settings/send-test-email', 'POST', $this, 'send_test_email', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('settings/currencies', 'GET', $this, 'get_currencies', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('settings/articles', 'GET', $this, 'get_articles', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('categories-articles', 'GET', $this, 'get_categories', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('settings/vats', 'GET', $this, 'get_vats', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('settings/payments-methods', 'GET', $this, 'get_payments_methods', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('settings/expenses-cat', 'GET', $this, 'get_expenses_categories', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('settings/currency/(?P<id>\d+)', 'GET', $this, 'get_currency', function () {
            return is_user_logged_in();
        });
        $this->routes->add_route('settings/vat/(?P<id>\d+)', 'GET', $this, 'get_vat', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('settings/upload-logo', 'POST', $this, 'upload_logo', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('settings/currencies', 'POST', $this, 'add_currency', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('settings/currencies/(?P<id>\d+)', 'PUT', $this, 'edit_currency', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('settings/currencies/(?P<id>\d+)', 'DELETE', $this, 'delete_currency', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('settings/articles', 'POST', $this, 'add_article', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('settings/articles/(?P<id>\d+)', 'PUT', $this, 'edit_article', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('settings/articles/(?P<id>\d+)', 'DELETE', $this, 'delete_article', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('settings/categories-articles', 'POST', $this, 'add_category', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('settings/categories-articles/(?P<id>\d+)', 'PUT', $this, 'edit_category', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('settings/categories-articles/(?P<id>\d+)', 'DELETE', $this, 'delete_category', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('settings/vats', 'POST', $this, 'add_vat', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('settings/vats/(?P<id>\d+)', 'PUT', $this, 'edit_vat', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('settings/vats/(?P<id>\d+)', 'DELETE', $this, 'delete_vat', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('settings/expenses-categories', 'POST', $this, 'add_expenses_categories', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('settings/expenses-categories/(?P<id>\d+)', 'PUT', $this, 'edit_expenses_categories', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('settings/expenses-categories/(?P<id>\d+)', 'DELETE', $this, 'delete_expenses_categories', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('settings/payments-methods', 'POST', $this, 'add_payment_method', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('settings/payments-methods/(?P<id>\d+)', 'PUT', $this, 'edit_payment_method', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('settings/payments-methods/(?P<id>\d+)', 'DELETE', $this, 'delete_payment_method', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('license/validate-license', 'POST', $this, 'validate_license', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('license/store-license', 'POST', $this, 'store_license', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('license/check-license', 'GET', $this, 'check_license', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('license/refresh-license', 'GET', $this, 'refresh_license', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('license/delete-license', 'DELETE', $this, 'delete_license', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('license/check-update', 'POST', $this, 'check_update_plugin', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('license/download-update', 'POST', $this, 'download_update_plugin', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('license/add-domain', 'POST', $this, 'add_domain', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('license/remove-domain', 'POST', $this, 'remove_domain', function () {
            return current_user_can('manage_options');
        });
        $this->routes->add_route('license/apply-affiliate', 'POST', $this, 'register_affiliate', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('e-invoicing/pdps', 'GET', $this, 'get_einvoicing_pdps', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('e-invoicing/settings', 'GET', $this, 'get_einvoicing_settings', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('e-invoicing/settings', 'POST', $this, 'save_einvoicing_settings', function () {
            return current_user_can('manage_options');
        });

        $this->routes->add_route('e-invoicing/pdp/test-connection', 'POST', $this, 'test_pdp_connection', function () {
            return current_user_can('manage_options');
        });

        $this->routes->register_routes();
    }

    private function get_einvoicing_allowed_keys(): array
    {
        return [
            'company_siren',
            'company_vat_number',
            'company_legal_address',
            'company_country_code',
            'company_vat_regime',
            'e_invoicing_enabled',
            'e_invoicing_transaction_types',
            'e_invoicing_facturx_profile',
            'e_invoicing_mode',
            'e_invoicing_auto_validate',
            // Legacy single-PDP keys (backward compat)
            'pdp_name',
            'pdp_api_endpoint',
            'pdp_api_key',
            'pdp_api_secret',
            'pdp_environment',
            // Multi-PDP support
            'pdp_configurations',
            'pdp_active',
        ];
    }

    /**
     * Retourne le catalogue de PDPs disponibles avec leurs champs de configuration.
     * Inclut les providers built-in + les addons enregistrés.
     */
    private function get_pdp_catalog(): array
    {
        return [
            'chorus_pro' => [
                'id'          => 'chorus_pro',
                'name'        => 'Chorus Pro',
                'description' => 'Plateforme officielle de l\'État pour la facturation des marchés publics (B2G). Obligatoire pour les factures adressées aux administrations publiques françaises.',
                'logo'        => 'government',
                'color'       => '#003189',
                'official'    => true,
                'builtin'     => true,
                'docs_url'    => 'https://chorus-pro.gouv.fr',
                'fields'      => [
                    ['key' => 'api_key',     'label' => 'Login PISTE (identifiant)',   'type' => 'text',     'placeholder' => 'votre.login@exemple.fr'],
                    ['key' => 'api_secret',  'label' => 'Mot de passe PISTE',           'type' => 'password', 'placeholder' => '••••••••'],
                    ['key' => 'siren',       'label' => 'SIREN de l\'entreprise',       'type' => 'text',     'placeholder' => '123456789'],
                    [
                        'key'     => 'environment',
                        'label'   => 'Environnement',
                        'type'    => 'select',
                        'options' => [
                            ['value' => 'sandbox',    'label' => 'Bac à sable (test)'],
                            ['value' => 'production', 'label' => 'Production'],
                        ],
                    ],
                ],
            ],
            'pennylane' => [
                'id'          => 'pennylane',
                'name'        => 'Pennylane',
                'description' => 'Solution comptable PDP agréée permettant la transmission automatique des factures électroniques et la synchronisation comptable en temps réel.',
                'logo'        => 'pennylane',
                'color'       => '#00C28B',
                'official'    => false,
                'builtin'     => true,
                'docs_url'    => 'https://pennylane.com',
                'fields'      => [
                    ['key' => 'api_key', 'label' => 'Clé API Pennylane', 'type' => 'password', 'placeholder' => 'pk_live_...'],
                    [
                        'key'     => 'environment',
                        'label'   => 'Environnement',
                        'type'    => 'select',
                        'options' => [
                            ['value' => 'sandbox',    'label' => 'Bac à sable (test)'],
                            ['value' => 'production', 'label' => 'Production'],
                        ],
                    ],
                ],
            ],
            'jefacture' => [
                'id'          => 'jefacture',
                'name'        => 'JeFacture',
                'description' => 'PDP agréé DGFiP pour la transmission sécurisée des factures électroniques B2B. Compatible Factur-X, UBL et CII.',
                'logo'        => 'jefacture',
                'color'       => '#F97316',
                'official'    => false,
                'builtin'     => true,
                'docs_url'    => 'https://jefacture.com',
                'fields'      => [
                    ['key' => 'api_key',    'label' => 'Clé API',    'type' => 'text',     'placeholder' => 'jf_api_...'],
                    ['key' => 'api_secret', 'label' => 'Secret API', 'type' => 'password', 'placeholder' => '••••••••'],
                    [
                        'key'     => 'environment',
                        'label'   => 'Environnement',
                        'type'    => 'select',
                        'options' => [
                            ['value' => 'sandbox',    'label' => 'Bac à sable (test)'],
                            ['value' => 'production', 'label' => 'Production'],
                        ],
                    ],
                ],
            ],
            'generic' => [
                'id'          => 'generic',
                'name'        => 'PDP Personnalisé',
                'description' => 'Connectez votre propre PDP agréé via son API REST. Utilisez cette option si votre PDP ne figure pas dans la liste ci-dessus.',
                'logo'        => 'globe',
                'color'       => '#6366F1',
                'official'    => false,
                'builtin'     => true,
                'docs_url'    => '',
                'fields'      => [
                    ['key' => 'name',         'label' => 'Nom du PDP',        'type' => 'text',     'placeholder' => 'Mon PDP agréé'],
                    ['key' => 'api_endpoint', 'label' => 'URL de l\'API',     'type' => 'url',      'placeholder' => 'https://api.monpdp.fr/v1'],
                    ['key' => 'api_key',      'label' => 'Clé API',           'type' => 'text',     'placeholder' => 'votre_cle_api'],
                    ['key' => 'api_secret',   'label' => 'Secret API',        'type' => 'password', 'placeholder' => '••••••••'],
                    [
                        'key'     => 'auth_style',
                        'label'   => 'Type d\'authentification',
                        'type'    => 'select',
                        'options' => [
                            ['value' => 'bearer', 'label' => 'Bearer Token'],
                            ['value' => 'apikey', 'label' => 'X-Api-Key Header'],
                            ['value' => 'basic',  'label' => 'Basic Auth'],
                        ],
                    ],
                    [
                        'key'     => 'environment',
                        'label'   => 'Environnement',
                        'type'    => 'select',
                        'options' => [
                            ['value' => 'sandbox',    'label' => 'Bac à sable (test)'],
                            ['value' => 'production', 'label' => 'Production'],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function get_einvoicing_settings()
    {
        global $wpdb;

        $settings_table = ECWP_TABLE_SETTINGS;
        $allowed        = $this->get_einvoicing_allowed_keys();
        $placeholders   = implode(',', array_fill(0, count($allowed), '%s'));

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta_key, meta_value FROM {$settings_table} WHERE meta_key IN ({$placeholders})",
                $allowed
            ),
            ARRAY_A
        );

        // JSON keys that should be decoded before returning
        $json_keys = ['pdp_configurations'];

        $settings = [];
        foreach ($rows as $row) {
            $value = $row['meta_value'];
            if (in_array($row['meta_key'], $json_keys, true) && !empty($value)) {
                $decoded = json_decode($value, true);
                $value   = (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
            }
            $settings[$row['meta_key']] = $value;
        }

        // Ensure pdp_configurations is always an object (never null)
        if (!isset($settings['pdp_configurations']) || !is_array($settings['pdp_configurations'])) {
            $settings['pdp_configurations'] = (object) [];
        }

        return rest_ensure_response($settings);
    }

    public function save_einvoicing_settings(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        global $wpdb;

        $settings_table = ECWP_TABLE_SETTINGS;
        $allowed        = array_flip($this->get_einvoicing_allowed_keys());
        $payload        = (array) $request->get_json_params();

        // Keys that must be stored as JSON (value is an array/object)
        $json_keys = ['pdp_configurations'];

        foreach ($payload as $meta_key => $meta_value) {
            $meta_key_sanitized = sanitize_key($meta_key);
            if (!isset($allowed[$meta_key_sanitized])) {
                continue;
            }

            // Sanitize value based on key type
            if (in_array($meta_key_sanitized, $json_keys, true)) {
                // Accept array or JSON string
                if (is_array($meta_value) || is_object($meta_value)) {
                    $meta_value = wp_json_encode($meta_value);
                } else {
                    $decoded = json_decode((string) $meta_value, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        continue; // Skip malformed JSON
                    }
                    $meta_value = wp_json_encode($decoded);
                }
            } elseif ($meta_key_sanitized === 'pdp_api_endpoint') {
                $meta_value = esc_url_raw((string) $meta_value);
            } elseif ($meta_key_sanitized === 'company_legal_address') {
                $meta_value = wp_kses_post((string) $meta_value);
            } else {
                $meta_value = sanitize_text_field((string) $meta_value);
            }

            $existing = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$settings_table} WHERE meta_key = %s",
                    $meta_key_sanitized
                )
            );

            if ($existing > 0) {
                $wpdb->update(
                    $settings_table,
                    ['meta_value' => $meta_value],
                    ['meta_key'   => $meta_key_sanitized],
                    ['%s'],
                    ['%s']
                );
            } else {
                $wpdb->insert(
                    $settings_table,
                    ['meta_key' => $meta_key_sanitized, 'meta_value' => $meta_value],
                    ['%s', '%s']
                );
            }

            wp_cache_delete('ecwp_setting_' . $meta_key_sanitized, 'ecwp_settings');
        }

        wp_cache_delete('ecwp_settings', 'ecwp_settings');

        return rest_ensure_response([
            'success' => true,
            'message' => __('Réglages de facturation électronique enregistrés.', 'my-easy-compta'),
        ]);
    }

    public function get_einvoicing_pdps()
    {
        // Start with the built-in catalog
        $catalog = $this->get_pdp_catalog();

        // Mark built-in providers as available
        foreach ($catalog as $id => &$pdp) {
            $pdp['available'] = true;
            $pdp['addon']     = false;
        }
        unset($pdp);

        // Merge in any registered addon providers
        if (class_exists('ECWP\\EInvoicing\\Workflow\\ProviderManager')) {
            $providers = \ECWP\EInvoicing\Workflow\ProviderManager::getInstance()->getProviders();
            foreach ($providers as $id => $provider) {
                if (!isset($catalog[$id])) {
                    // Addon-only provider not in built-in catalog
                    $catalog[$id] = [
                        'id'          => $id,
                        'name'        => $provider->getName(),
                        'description' => '',
                        'logo'        => 'plug',
                        'color'       => '#8B5CF6',
                        'official'    => false,
                        'builtin'     => false,
                        'available'   => true,
                        'addon'       => true,
                        'fields'      => [],
                    ];
                } else {
                    $catalog[$id]['available'] = true;
                    $catalog[$id]['addon']     = true;
                }
            }
        }

        return rest_ensure_response(array_values($catalog));
    }

    /**
     * Teste la connexion à un PDP.
     * Supporte les providers built-in et les handlers addons.
     */
    public function test_pdp_connection(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        $pdp_id = sanitize_key((string) $request->get_param('pdp_id'));
        $config = (array) ($request->get_param('config') ?? []);

        // Essai avec les providers built-in
        $built_in_result = $this->test_builtin_pdp($pdp_id, $config);
        if ($built_in_result !== null) {
            return rest_ensure_response($built_in_result);
        }

        // Essai avec un handler addon
        $handler = apply_filters('myeasycompta_pdp_test_connection_handler', null, $request);
        if (is_callable($handler)) {
            $result = call_user_func($handler, $request);
            if (is_wp_error($result)) {
                return $result;
            }
            return rest_ensure_response($result);
        }

        return new \WP_Error(
            'pdp_not_found',
            sprintf(
                __('Provider PDP "%s" inconnu ou non disponible.', 'my-easy-compta'),
                esc_html($pdp_id)
            ),
            ['status' => 400]
        );
    }

    /**
     * Tente un test de connexion sur un provider built-in.
     *
     * @param string $pdp_id
     * @param array  $config
     * @return array|null Résultat ou null si provider non trouvé
     */
    private function test_builtin_pdp(string $pdp_id, array $config): ?array
    {
        $map = [
            'chorus_pro' => \ECWP\EInvoicing\Providers\ChorusProProvider::class,
            'pennylane'  => \ECWP\EInvoicing\Providers\PennylaneProvider::class,
            'jefacture'  => \ECWP\EInvoicing\Providers\JeFactureProvider::class,
            'generic'    => \ECWP\EInvoicing\Providers\GenericProvider::class,
        ];

        if (!isset($map[$pdp_id])) {
            return null;
        }

        $class = $map[$pdp_id];
        if (!class_exists($class)) {
            return null;
        }

        /** @var \ECWP\EInvoicing\Providers\ChorusProProvider $provider */
        $provider = new $class($config);
        return $provider->testConnection();
    }

    /**
     * @return [type]
     */
    public function get_settings()
    {
        global $wpdb;
        $settings_table = ECWP_TABLE_SETTINGS;
        $results = $wpdb->get_results("SELECT meta_key, meta_value FROM {$settings_table}", OBJECT_K);

        $invoices_table = ECWP_TABLE_INVOICES;
        $quotes_table = ECWP_TABLE_QUOTES;
        $last_invoice_id = $wpdb->get_var("SELECT MAX(number) AS last_id FROM {$invoices_table}");
        $last_quote_id = $wpdb->get_var("SELECT MAX(number) AS last_id FROM {$quotes_table}");

        if (empty($last_invoice_id)) {
            $settings_table = ECWP_TABLE_SETTINGS;
            $last_invoice_id = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$settings_table} WHERE meta_key = %s", 'invoice_first'));
        } else {
            $last_invoice_id += 1;
        }

        if (empty($last_quote_id)) {
            $settings_table = ECWP_TABLE_SETTINGS;
            $last_quote_id = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$settings_table} WHERE meta_key = %s", 'quote_first'));
        } else {
            $last_quote_id += 1;
        }

        $email_defaults = [
            'ecwp_email_theme'             => 'dark',
            'ecwp_notify_quote_action'     => '1',
            'ecwp_notify_invoice_paid'     => '1',
            'ecwp_notify_partial_payment'  => '1',
            'ecwp_notify_new_client'       => '1',
            'ecwp_notify_quote_converted'  => '0',
            'ecwp_notify_backup_done'      => '1',
            'ecwp_notify_backup_deleted'   => '0',
            'ecwp_notify_planning_event'   => '1',
            'invoice_email_subject'        => __('Votre facture {numero_document}', 'my-easy-compta'),
            'invoice_email_content'        => '<p>' . __('Bonjour {nom_client},', 'my-easy-compta') . '</p><p>' . __('Veuillez trouver ci-joint votre facture <strong>{numero_document}</strong> d\'un montant de <strong>{montant_total}</strong>.', 'my-easy-compta') . '</p><p>' . __('Merci de procéder au règlement dans les délais indiqués sur le document.', 'my-easy-compta') . '</p><p>' . __('Cordialement,', 'my-easy-compta') . '</p>',
            'invoice_email_remind_subject' => __('Relance — Facture {numero_document} en attente de paiement', 'my-easy-compta'),
            'invoice_email_remind_content' => '<p>' . __('Bonjour {nom_client},', 'my-easy-compta') . '</p><p>' . __('Sauf erreur de votre part, nous n\'avons pas encore reçu le règlement de la facture <strong>{numero_document}</strong> d\'un montant de <strong>{montant_total}</strong>.', 'my-easy-compta') . '</p><p>' . __('Nous vous serions reconnaissants de bien vouloir régulariser cette situation dans les meilleurs délais.', 'my-easy-compta') . '</p><p>' . __('Cordialement,', 'my-easy-compta') . '</p>',
            'quote_email_subject'          => __('Votre devis {numero_document}', 'my-easy-compta'),
            'quote_email_content'          => '<p>' . __('Bonjour {nom_client},', 'my-easy-compta') . '</p><p>' . __('Veuillez trouver ci-joint votre devis <strong>{numero_document}</strong> d\'un montant de <strong>{montant_total}</strong>.', 'my-easy-compta') . '</p><p>' . __('Ce devis est valable 30 jours.', 'my-easy-compta') . '</p><p>' . __('Cordialement,', 'my-easy-compta') . '</p>',
        ];

        $settings = [];
        foreach ($results as $key => $value) {
            $settings[$key] = $value->meta_value;
        }

        // Inject defaults for email templates that have never been saved
        foreach ($email_defaults as $key => $default) {
            if (empty($settings[$key])) {
                $settings[$key] = $default;
            }
        }

        $settings['last_quote_id'] = $last_quote_id;
        $settings['last_invoice_id'] = $last_invoice_id;

        return rest_ensure_response($settings);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function get_format_date()
    {
        global $wpdb;
        $settings_table = ECWP_TABLE_SETTINGS;
        $results = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$settings_table} WHERE meta_key = %s", 'date_format'));

        return rest_ensure_response($this->convert_date_format($results));
    }
    public function convert_date_format($user_format)
    {
        $format_map = array(
            'DD-MM-YYYY' => 'd-m-Y',
            'MM-DD-YYYY' => 'm-d-Y',
            'YYYY-MM-DD' => 'Y-m-d',
            'YYYY/MM/DD' => 'Y/m/d',
            'DD/MM/YYYY' => 'd/m/Y',
            'MM/DD/YYYY' => 'm/d/Y',
            'YYYY.MM.DD' => 'Y.m.d',
            'DD.MM.YYYY' => 'd.m.Y',
            'MM.DD.YYYY' => 'm.d.Y',
        );

        return isset($format_map[$user_format]) ? $format_map[$user_format] : 'Y-m-d';
    }
    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function save_settings(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('rest_nonce_invalid', __('Invalid nonce', 'my-easy-compta'), array('status' => 403));
        }

        global $wpdb;
        $settings_table = ECWP_TABLE_SETTINGS;
        $settings = $request->get_params();

        foreach ($settings as $meta_key => $meta_value) {
            if ($meta_key === 'logo_url') {
                $meta_value = esc_url_raw($meta_value);
            } else {
                $meta_value = wp_kses_post($meta_value);
            }
            $meta_key_sanitized = sanitize_key($meta_key);
            $existing_setting = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$settings_table} WHERE meta_key = %s",
                    $meta_key_sanitized
                )
            );

            if ($existing_setting > 0) {
                $wpdb->update(
                    $settings_table,
                    array('meta_value' => $meta_value),
                    array('meta_key' => $meta_key_sanitized),
                    array('%s'),
                    array('%s')
                );
            } else {
                $wpdb->insert(
                    $settings_table,
                    array(
                        'meta_key' => $meta_key_sanitized,
                        'meta_value' => $meta_value,
                    ),
                    array(
                        '%s',
                        '%s',
                    )
                );
            }

            // Invalider le cache pour ce paramètre
            wp_cache_delete('ecwp_setting_' . $meta_key_sanitized, 'ecwp_settings');
        }

        // Invalider le cache global des paramètres
        wp_cache_delete('ecwp_settings', 'ecwp_settings');

        return rest_ensure_response(array('success' => true, 'message' => __('Settings saved successfully', 'my-easy-compta')));
    }

    /**
     * @return [type]
     */
    public function get_articles()
    {
        global $wpdb;
        $articles_table = ECWP_TABLE_ARTICLES;
        $articles_categories_table = ECWP_TABLE_ARTICLES_CATEGORIES;
        $articles = $wpdb->get_results("SELECT * FROM {$articles_table}", ARRAY_A);
        $categories = $wpdb->get_results("SELECT * FROM {$articles_categories_table}", ARRAY_A);
        $response_data = [
            'articles' => $articles,
            'categories' => $categories,
        ];
        return rest_ensure_response($response_data);
    }
    /**
     * @return [type]
     */
    public function get_categories()
    {
        global $wpdb;
        $articles_categories_table = ECWP_TABLE_ARTICLES_CATEGORIES;
        $categories = $wpdb->get_results("SELECT * FROM {$articles_categories_table}", ARRAY_A);
        return rest_ensure_response($categories);
    }

    /**
     * @return [type]
     */
    public function get_currencies()
    {
        // Utiliser le cache pour améliorer les performances
        $cache_key = 'ecwp_currencies';
        $cached = wp_cache_get($cache_key, 'ecwp_data');

        if (false !== $cached) {
            return rest_ensure_response($cached);
        }

        global $wpdb;
        $currencies_table = ECWP_TABLE_CURRENCY;
        $currencies = $wpdb->get_results("SELECT * FROM {$currencies_table}", ARRAY_A);

        // Mettre en cache pendant 1 heure
        wp_cache_set($cache_key, $currencies, 'ecwp_data', 3600);

        return rest_ensure_response($currencies);
    }

    /**
     * @return [type]
     */
    public function get_vats()
    {
        // Utiliser le cache pour améliorer les performances
        $cache_key = 'ecwp_vats';
        $cached = wp_cache_get($cache_key, 'ecwp_data');

        if (false !== $cached) {
            return rest_ensure_response($cached);
        }

        global $wpdb;
        $vats_table = ECWP_TABLE_VATS;
        $vats = $wpdb->get_results("SELECT * FROM {$vats_table}", ARRAY_A);

        // Mettre en cache pendant 1 heure
        wp_cache_set($cache_key, $vats, 'ecwp_data', 3600);

        return rest_ensure_response($vats);
    }

    /**
     * @return [type]
     */
    public function get_payments_methods()
    {
        // Utiliser le cache pour améliorer les performances
        $cache_key = 'ecwp_payments_methods';
        $cached = wp_cache_get($cache_key, 'ecwp_data');

        if (false !== $cached) {
            return rest_ensure_response($cached);
        }

        global $wpdb;
        $payments_table = ECWP_TABLE_PAYMENTS_METHODS;
        $payments = $wpdb->get_results("SELECT * FROM {$payments_table}", ARRAY_A);

        // Mettre en cache pendant 1 heure
        wp_cache_set($cache_key, $payments, 'ecwp_data', 3600);

        return rest_ensure_response($payments);
    }
    /**
     * @return [type]
     */
    public function get_expenses_categories()
    {
        // Utiliser le cache pour améliorer les performances
        $cache_key = 'ecwp_expenses_categories';
        $cached = wp_cache_get($cache_key, 'ecwp_data');

        if (false !== $cached) {
            return rest_ensure_response($cached);
        }

        global $wpdb;
        $exps_table = ECWP_TABLE_EXPENSES_CATEGORIES;
        $exps = $wpdb->get_results("SELECT * FROM {$exps_table}", ARRAY_A);

        // Mettre en cache pendant 1 heure
        wp_cache_set($cache_key, $exps, 'ecwp_data', 3600);

        return rest_ensure_response($exps);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function get_currency(\WP_REST_Request $request)
    {
        global $wpdb;
        $currency_id = absint($request->get_param('id'));
        $currencies_table = ECWP_TABLE_CURRENCY;
        $currency_data = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$currencies_table} WHERE id = %d", $currency_id)
        );

        if (!$currency_data) {
            return new \WP_Error('currency_not_found', 'Currency not found', array('status' => 404));
        }

        return rest_ensure_response($currency_data);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function get_vat(\WP_REST_Request $request)
    {
        global $wpdb;
        $vat_id = $request->get_param('id');
        $vats_table = ECWP_TABLE_VATS;
        $vat_data = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$vats_table} WHERE id = %d", $vat_id)
        );

        if (!$vat_data) {
            return new \WP_Error('vat_not_found', 'VAT not found', array('status' => 404));
        }

        return rest_ensure_response($vat_data);
    }

    /**
     * @return [type]
     */
    public function upload_logo()
    {
        if (isset($_SERVER['HTTP_X_WP_NONCE'])) {
            $nonce = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_WP_NONCE']));
            if (!wp_verify_nonce($nonce, 'wp_rest')) {
                return new \WP_Error('invalid_nonce', 'Nonce verification failed.');
            }
        } else {
            return new \WP_Error('missing_nonce', 'Nonce is missing.');
        }

        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        global $wp_filesystem;

        $upload_dir = wp_upload_dir();
        $upload_path = trailingslashit($upload_dir['basedir']) . 'logo/';

        if (!$wp_filesystem->is_dir($upload_path)) {
            $wp_filesystem->mkdir($upload_path);
        }

        if (!isset($_FILES['logo']) || !isset($_FILES['logo']['tmp_name']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            return new \WP_Error('file_upload_error', 'File upload error.');
        }

        $file_name = sanitize_file_name($_FILES['logo']['name']);
        $file_type = wp_check_filetype($file_name);
        $file_tmp_name = sanitize_text_field($_FILES['logo']['tmp_name']);
        $file_size = absint($_FILES['logo']['size']);
        $file_error = absint($_FILES['logo']['error']);

        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        if (!in_array($file_type['ext'], $allowed_types)) {
            return new \WP_REST_Response(array('message' => 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.'), 400);
        }

        $max_file_size = 2 * 1024 * 1024;
        if ($file_size > $max_file_size) {
            return new \WP_REST_Response(array('message' => 'File size exceeds the maximum allowed size of 2 MB.'), 400);
        }

        $file = array(
            'name' => $file_name,
            'type' => $file_type['type'],
            'tmp_name' => $file_tmp_name,
            'size' => $file_size,
            'error' => $file_error,
        );

        $upload_overrides = array('test_form' => false);
        $upload = wp_handle_upload($file, $upload_overrides);

        if (isset($upload['error'])) {
            return new \WP_REST_Response(array('message' => 'Failed to save image: ' . $upload['error']), 500);
        }

        return rest_ensure_response(array('url' => $upload['url'], 'path' => $upload['file']));
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function add_currency(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $params = $request->get_json_params();
        $name = sanitize_text_field($params['name']);
        $symbol = sanitize_text_field($params['symbol']);
        $code = sanitize_text_field($params['code']);

        $result = $wpdb->insert(
            ECWP_TABLE_CURRENCY,
            [
                'name' => $name,
                'symbol' => $symbol,
                'code' => $code,
            ]
        );

        if ($result === false) {
            return new \WP_Error('currency_creation_failed', 'Failed to create currency', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $wpdb->insert_id, 'name' => $name, 'symbol' => $symbol, 'code' => $code], 201);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    function edit_currency(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $params = $request->get_json_params();
        $id = absint($request['id']);
        $name = sanitize_text_field($params['name']);
        $symbol = sanitize_text_field($params['symbol']);
        $code = sanitize_text_field($params['code']);

        $result = $wpdb->update(
            ECWP_TABLE_CURRENCY,
            [
                'name' => $name,
                'symbol' => $symbol,
                'code' => $code,
            ],
            ['id' => $id]
        );

        if ($result === false) {
            return new \WP_Error('currency_update_failed', 'Failed to update currency', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $id, 'name' => $name, 'symbol' => $symbol, 'code' => $code], 200);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    function delete_currency(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $id = absint($request['id']);

        $result = $wpdb->delete(
            ECWP_TABLE_CURRENCY,
            ['id' => $id]
        );

        if ($result === false) {
            return new \WP_Error('currency_deletion_failed', 'Failed to delete currency', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $id], 200);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function add_article(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $params = $request->get_json_params();
        $ref = sanitize_text_field($params['ref']);
        $name = sanitize_text_field($params['name']);
        $description = sanitize_text_field($params['description']);
        $unit_price = sanitize_text_field($params['unit_price']);

        $result = $wpdb->insert(
            ECWP_TABLE_ARTICLES,
            [
                'ref' => $ref,
                'name' => $name,
                'description' => $description,
                'unit_price' => $unit_price,
            ]
        );

        if ($result === false) {
            return new \WP_Error('currency_creation_failed', 'Failed to create currency', ['status' => 500]);
        }

        return new \WP_REST_Response([
            'id' => $wpdb->insert_id,
            'name' => $name,
            'description' => $description,
            'unit_price' => $unit_price
        ], 201);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    function edit_article(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $params = $request->get_json_params();
        $id = absint($request['id']);
        $ref = sanitize_text_field($params['ref']);
        $name = sanitize_text_field($params['name']);
        $description = sanitize_text_field($params['description']);
        $unit_price = sanitize_text_field($params['unit_price']);

        $result = $wpdb->update(
            ECWP_TABLE_ARTICLES,
            [
                'ref' => $ref,
                'name' => $name,
                'description' => $description,
                'unit_price' => $unit_price,
            ],
            ['id' => $id]
        );

        if ($result === false) {
            return new \WP_Error('currency_update_failed', 'Failed to update currency', ['status' => 500]);
        }

        return new \WP_REST_Response([
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'unit_price' => $unit_price
        ], 200);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    function delete_article(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $id = absint($request['id']);

        $result = $wpdb->delete(
            ECWP_TABLE_ARTICLES,
            ['id' => $id]
        );

        if ($result === false) {
            return new \WP_Error('article_deletion_failed', 'Failed to delete article', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $id], 200);
    }
    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    function delete_category(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $id = absint($request['id']);

        $result = $wpdb->delete(
            ECWP_TABLE_ARTICLES_CATEGORIES,
            ['id' => $id]
        );

        if ($result === false) {
            return new \WP_Error('article_deletion_failed', 'Failed to delete article', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $id], 200);
    }

    public function add_category(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }

        global $wpdb;
        $params = $request->get_params();
        $name = sanitize_text_field($params['name']);

        if (empty($name)) {
            return new \WP_Error('missing_name', 'Category name is required', array('status' => 400));
        }

        $result = $wpdb->insert(
            ECWP_TABLE_ARTICLES_CATEGORIES,
            array('name' => $name)
        );

        if ($result === false) {
            return new \WP_Error('category_creation_failed', 'Failed to create category', array('status' => 500));
        }

        return new \WP_REST_Response(['success' => true, 'id' => $wpdb->insert_id, 'message' => 'Category created successfully'], 200);
    }

    public function edit_category(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }

        global $wpdb;
        $id = absint($request['id']);
        $params = $request->get_params();
        $name = sanitize_text_field($params['name']);

        if (empty($name)) {
            return new \WP_Error('missing_name', 'Category name is required', array('status' => 400));
        }

        $result = $wpdb->update(
            ECWP_TABLE_ARTICLES_CATEGORIES,
            array('name' => $name),
            array('id' => $id)
        );

        if ($result === false) {
            return new \WP_Error('category_update_failed', 'Failed to update category', array('status' => 500));
        }

        return new \WP_REST_Response(['success' => true, 'id' => $id, 'message' => 'Category updated successfully'], 200);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function add_vat(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $params = $request->get_json_params();
        $description = sanitize_text_field($params['description']);
        $rate = floatval($params['rate']);

        $result = $wpdb->insert(
            ECWP_TABLE_VATS,
            [
                'description' => $description,
                'rate' => $rate,
            ]
        );

        if ($result === false) {
            return new \WP_Error('vat_creation_failed', 'Failed to create VAT', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $wpdb->insert_id, 'description' => $description, 'rate' => $rate], 201);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function edit_vat(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $params = $request->get_json_params();
        $id = absint($request['id']);
        $description = sanitize_text_field($params['description']);
        $rate = floatval($params['rate']);

        $result = $wpdb->update(
            ECWP_TABLE_VATS,
            [
                'description' => $description,
                'rate' => $rate,
            ],
            ['id' => $id]
        );

        if ($result === false) {
            return new \WP_Error('vat_update_failed', 'Failed to update VAT', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $id, 'description' => $description, 'rate' => $rate], 200);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function delete_vat(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $id = absint($request['id']);

        $result = $wpdb->delete(
            ECWP_TABLE_VATS,
            ['id' => $id]
        );

        if ($result === false) {
            return new \WP_Error('vat_deletion_failed', 'Failed to delete VAT', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $id], 200);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function add_expenses_categories(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $params = $request->get_json_params();
        $name = sanitize_text_field($params['name']);

        $result = $wpdb->insert(
            ECWP_TABLE_EXPENSES_CATEGORIES,
            [
                'name' => $name,
            ]
        );

        if ($result === false) {
            return new \WP_Error('vat_creation_failed', 'Failed to create VAT', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $wpdb->insert_id, 'name' => $name], 201);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function edit_expenses_categories(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $params = $request->get_json_params();
        $id = absint($request['id']);
        $name = sanitize_text_field($params['name']);

        $result = $wpdb->update(
            ECWP_TABLE_EXPENSES_CATEGORIES,
            [
                'name' => $name,
            ],
            ['id' => $id]
        );

        if ($result === false) {
            return new \WP_Error('expense_update_failed', 'Failed to update Expense category', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $id, 'name' => $name], 200);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function delete_expenses_categories(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $id = absint($request['id']);

        $result = $wpdb->delete(
            ECWP_TABLE_EXPENSES_CATEGORIES,
            ['id' => $id]
        );

        if ($result === false) {
            return new \WP_Error('expense_deletion_failed', 'Failed to delete expense category', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $id], 200);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function add_payment_method(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $params = $request->get_json_params();
        $method_name = sanitize_text_field($params['method_name']);

        $result = $wpdb->insert(
            ECWP_TABLE_PAYMENTS_METHODS,
            [
                'method_name' => $method_name,
            ]
        );

        if ($result === false) {
            return new \WP_Error('method_payment_creation_failed', 'Failed to create Payment method', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $wpdb->insert_id, 'method_name' => $method_name], 201);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function edit_payment_method(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $params = $request->get_json_params();
        $id = absint($request['id']);
        $method_name = sanitize_text_field($params['method_name']);

        $result = $wpdb->update(
            ECWP_TABLE_PAYMENTS_METHODS,
            [
                'method_name' => $method_name,
            ],
            ['id' => $id]
        );

        if ($result === false) {
            return new \WP_Error('payment_update_failed', 'Failed to update Payment method', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $id, 'method_name' => $method_name], 200);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return [type]
     */
    public function delete_payment_method(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        global $wpdb;
        $id = absint($request['id']);

        $result = $wpdb->delete(
            ECWP_TABLE_PAYMENTS_METHODS,
            ['id' => $id]
        );

        if ($result === false) {
            return new \WP_Error('payment_deletion_failed', 'Failed to delete Payment method', ['status' => 500]);
        }

        return new \WP_REST_Response(['id' => $id], 200);
    }

    /**
     * Déchiffre une clé de licence avec support de migration depuis l'ancien système
     * 
     * @param string $encrypted_license_key La clé chiffrée
     * @return string|false La clé déchiffrée ou false en cas d'échec
     */
    private function decrypt_license_key($encrypted_license_key)
    {
        if (empty($encrypted_license_key)) {
            return false;
        }

        try {
            // Essayer d'abord avec le nouveau système (AES-256-CBC)
            $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
            $decrypted = $encrypt->decrypt($encrypted_license_key);

            // Si succès, retourner la clé déchiffrée
            if ($decrypted !== false && !empty($decrypted)) {
                return $decrypted;
            }
        } catch (\Exception $e) {
            // Fallback: try legacy AES-128-ECB decryption for migration from older plugin versions.
            if (defined('ECWP_SECRET_KEY')) {
                $decrypted = openssl_decrypt(
                    base64_decode($encrypted_license_key, true),
                    'AES-128-ECB',
                    ECWP_SECRET_KEY
                );

                // If legacy decryption succeeded, re-encrypt with the new system and save.
                if ($decrypted !== false && !empty($decrypted)) {
                    try {
                        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
                        $new_encrypted = $encrypt->encrypt($decrypted);
                        update_option('ecwp_client_license_key', $new_encrypted);
                    } catch (\Exception $re_encrypt_e) {
                        // Re-encryption failed; return the decrypted key as-is.
                    }
                    return $decrypted;
                }
            }
        }

        return false;
    }

    public function get_setting($meta_key)
    {
        // Utiliser le cache pour améliorer les performances
        $cache_key = 'ecwp_setting_' . $meta_key;
        $cached = wp_cache_get($cache_key, 'ecwp_settings');

        if (false !== $cached) {
            return $cached;
        }

        global $wpdb;
        $settings_table = ECWP_TABLE_SETTINGS;
        $meta_value = $wpdb->get_var(
            $wpdb->prepare("SELECT meta_value FROM {$settings_table} WHERE meta_key = %s", $meta_key)
        );

        // Mettre en cache pendant 1 heure
        wp_cache_set($cache_key, $meta_value, 'ecwp_settings', 3600);

        return $meta_value;
    }

    /** Mapping short addon slug → display name + full plugin directory slug */
    private const ADDON_MAP = [
        'email'          => ['name' => 'myEC Emails',            'plugin' => 'my-easy-compta-email'],
        'export'         => ['name' => 'myEC Export',            'plugin' => 'my-easy-compta-export'],
        'stats'          => ['name' => 'myEC Statistiques',      'plugin' => 'my-easy-compta-stats'],
        'planning'       => ['name' => 'myEC Planning',          'plugin' => 'my-easy-compta-planning'],
        'payment'        => ['name' => 'myEC Paiement en ligne', 'plugin' => 'my-easy-compta-payment'],
        'signature'      => ['name' => 'myEC Signature',         'plugin' => 'my-easy-compta-signature'],
        'backup'         => ['name' => 'myEC Sauvegarde',        'plugin' => 'my-easy-compta-backup'],
        'recurring'      => ['name' => 'myEC Récurrentes',       'plugin' => 'myeasycompta-recurring-invoices'],
        'advance'        => ['name' => 'myEC Avancé',            'plugin' => 'my-easy-compta-advance'],
        'siret'          => ['name' => 'myEC SIRET',             'plugin' => 'my-easy-compta-siret'],
        'qrcode_stripe'  => ['name' => 'myEC QR Code & Stripe',  'plugin' => 'my-easy-compta-qrcode-stripe'],
        'user'           => ['name' => 'myEC Espace Client',     'plugin' => 'my-easy-compta-user'],
        'woo'            => ['name' => 'myEC WooCommerce',       'plugin' => 'my-easy-compta-woo'],
        'surecart'       => ['name' => 'myEC SureCart',          'plugin' => 'my-easy-compta-surecart'],
        'online-quote'   => ['name' => 'myEC Devis en ligne',    'plugin' => 'my-easy-compta-online-quote'],
    ];

    /**
     * Normalise la réponse du nouveau serveur de licences pour la compatibilité Vue.
     * Construit l'objet `plugins{}` attendu par le frontend à partir du tableau `addons[]`.
     */
    private function normalize_license_data(array $data): array
    {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $installed = get_plugins();
        $installed_versions = [];
        foreach ($installed as $path => $info) {
            $slug = dirname(plugin_basename($path));
            $installed_versions[$slug] = $info['Version'];
        }

        $addons = $data['addons'] ?? [];
        if (in_array('_bundle', $addons, true)) {
            $addons = array_keys(self::ADDON_MAP);
        }

        // Table de correspondance pour les slugs envoyés par le serveur de licences avec variants
        $slug_aliases = ['online_quote' => 'online-quote'];

        $plugins_display = [];
        foreach ($addons as $short) {
            $short_lookup = $slug_aliases[$short] ?? $short;
            $map = self::ADDON_MAP[$short_lookup] ?? ['name' => ucwords(str_replace(['-', '_'], ' ', $short)), 'plugin' => 'my-easy-compta-' . $short_lookup];
            $full_slug   = $map['plugin'];
            $latest      = get_option('mec_addon_version_' . $short, '');
            $installed_v = $installed_versions[$full_slug] ?? null;
            $plugins_display[$full_slug] = [
                'product_name'  => $map['name'],
                'product_slug'  => $short,
                'version'       => $latest ?: ($installed_v ?: '—'),
                'installed'     => $installed_v !== null,
                'installed_ver' => $installed_v,
            ];
        }

        $data['plugins']     = $plugins_display;
        $data['client_name'] = $data['customer_name'] ?? ($data['email'] ?? '');
        $data['license_type'] = $data['plan'] ?? 'Standard';

        return $data;
    }

    /** Convertit un slug plugin complet en slug court pour l'endpoint /download */
    private function to_short_slug(string $plugin_slug): string
    {
        static $map = [
            'myeasycompta-recurring-invoices' => 'recurring',
            'my-easy-compta-qrcode-stripe'    => 'qrcode_stripe',
        ];
        return $map[$plugin_slug] ?? str_replace('my-easy-compta-', '', $plugin_slug);
    }

    public function validate_license(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        $license_key = sanitize_text_field($request->get_param('license_key'));

        if (empty($license_key)) {
            return new \WP_REST_Response(['valid' => false, 'message' => 'License key is required.'], 400);
        }

        $current_domain = $this->get_current_domain();
        $body = $this->get_validate_license($license_key, true);

        if (!$body || isset($body['error'])) {
            return new \WP_REST_Response([
                'valid' => false,
                'message' => $body['message'] ?? 'Erreur de connexion au serveur de licences.'
            ], 500);
        }

        $http_code = $body['valid'] ? 200 : 403;
        return new \WP_REST_Response($body, $http_code);
    }

    public function store_license(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }
        $license_key = sanitize_text_field($request->get_param('license_key'));
        $license_data = $request->get_param('license_data');

        // Utiliser la classe de chiffrement sécurisée
        $encrypt = new \ECWP\Admin\Encrypt\ECWP_Encrypt();
        $encrypted_license_key = $encrypt->encrypt($license_key);
        update_option('ecwp_client_license_key', $encrypted_license_key);
        update_option('ecwp_client_license_data', $license_data);

        return new \WP_REST_Response(['success' => true, 'message' => 'License stored successfully'], 200);
    }

    public function check_license(\WP_REST_Request $request)
    {

        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }

        $encrypted_license_key = get_option('ecwp_client_license_key');
        $license_data = get_option('ecwp_client_license_data');

        if (empty($encrypted_license_key) || empty($license_data)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'License not found.'], 404);
        }

        $license_key = $this->decrypt_license_key($encrypted_license_key);

        if ($license_key === false) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Failed to decrypt license key.'], 500);
        }

        $raw_data     = $this->get_validate_license($license_key);
        $license_data = $this->normalize_license_data($raw_data);

        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $installed_versions = [];
        foreach (get_plugins() as $path => $info) {
            $installed_versions[dirname(plugin_basename($path))] = $info['Version'];
        }

        return new \WP_REST_Response([
            'success'            => true,
            'valid'              => true,
            'license_data'       => $license_data,
            'installed_versions' => $installed_versions,
        ], 200);
    }

    public function refresh_license(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }

        $encrypted_license_key = get_option('ecwp_client_license_key');

        if (empty($encrypted_license_key)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'License key not found.'], 404);
        }

        $license_key = $this->decrypt_license_key($encrypted_license_key);

        if ($license_key === false) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Failed to decrypt license key.'], 500);
        }

        $raw_data     = $this->get_validate_license($license_key);
        $license_data = $this->normalize_license_data($raw_data);

        if (!$license_data || !($license_data['valid'] ?? false)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'License validation failed.'], 500);
        }

        update_option('ecwp_client_license_data', $license_data);

        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $installed_versions = [];
        foreach (get_plugins() as $path => $info) {
            $installed_versions[dirname(plugin_basename($path))] = $info['Version'];
        }

        return new \WP_REST_Response([
            'success'            => true,
            'valid'              => true,
            'license_data'       => $license_data,
            'installed_versions' => $installed_versions,
        ], 200);
    }

    /**
     * Normalise un domaine (enlève http/https, www, trailing slash)
     *
     * @param string $domain Le domaine à normaliser
     * @return string Le domaine normalisé
     */
    private function normalize_domain($domain)
    {
        // Enlever http:// ou https://
        $domain = preg_replace('#^https?://#', '', $domain);
        // Enlever www.
        $domain = preg_replace('#^www\.#', '', $domain);
        // Enlever le trailing slash
        $domain = rtrim($domain, '/');
        // Enlever les espaces et convertir en minuscules
        $domain = strtolower(trim($domain));
        return $domain;
    }

    /**
     * Récupère le domaine actuel du site normalisé
     *
     * @return string Le domaine actuel normalisé
     */
    private function get_current_domain()
    {
        $site_url = site_url();
        $domain = $this->normalize_domain($site_url);

        return $domain;
    }

    /**
     * Vérifie si l'URL de licence est en environnement local
     *
     * @return bool True si l'URL de licence est locale
     */
    private function is_license_url_local()
    {
        $license_url = ECWP_URL_LICENSE;
        $license_domain = $this->normalize_domain($license_url);

        return (
            strpos($license_domain, '.local') !== false ||
            strpos($license_domain, 'localhost') !== false ||
            strpos($license_domain, '127.0.0.1') !== false ||
            strpos($license_url, '.local') !== false ||
            strpos($license_url, 'localhost') !== false ||
            strpos($license_url, '127.0.0.1') !== false
        );
    }

    public function get_validate_license($license_key, bool $auto_activate = false)
    {
        if (empty($license_key)) {
            return ['valid' => false, 'message' => 'License key is required.'];
        }

        $current_domain   = $this->get_current_domain();
        $api_url          = ECWP_URL_LICENSE . '/wp-json/mec-license/v1/check';
        $is_license_local = $this->is_license_url_local();

        $response = wp_remote_post($api_url, [
            'body'      => wp_json_encode([
                'license_key'   => sanitize_text_field($license_key),
                'domain'        => $current_domain,
                'auto_activate' => $auto_activate,
            ]),
            'headers'   => ['Content-Type' => 'application/json'],
            'timeout'   => 15,
            'sslverify' => !$is_license_local,
        ]);

        if (is_wp_error($response)) {
            return [
                'valid'   => false,
                'message' => 'Failed to validate license: ' . $response->get_error_message(),
            ];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body          = json_decode(wp_remote_retrieve_body($response), true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($body)) {
            return [
                'valid'   => false,
                'message' => 'Invalid response from license server. HTTP ' . $response_code,
            ];
        }

        return $body;
    }

    public function delete_license($request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }

        delete_option('ecwp_client_license_key');
        delete_option('ecwp_client_license_data');

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('License data deleted successfully.', 'my-easy-compta'),
        ], 200);
    }

    function check_update_plugin(\WP_REST_Request $request)
    {
        $plugin_slug     = sanitize_text_field($request->get_param('plugin_slug'));
        $current_version = sanitize_text_field($request->get_param('current_version') ?? '');

        if (empty($plugin_slug)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Plugin slug is required.'], 400);
        }

        $short_slug     = $this->to_short_slug($plugin_slug);
        $latest_version = get_option('mec_addon_version_' . $short_slug, '');

        // If no version info available, assume up to date
        if (empty($latest_version)) {
            return new \WP_REST_Response(['success' => true, 'update_available' => false, 'new_version' => null], 200);
        }

        $installed = $current_version ?: '';
        $update_available = $installed ? version_compare($latest_version, $installed, '>') : false;

        return new \WP_REST_Response([
            'success'          => true,
            'update_available' => $update_available,
            'new_version'      => $latest_version,
        ], 200);
    }

    public function download_update_plugin(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }

        $plugin_slug = sanitize_text_field($request->get_param('plugin_slug'));
        $current_domain = $this->get_current_domain();

        $encrypted_license_key = get_option('ecwp_client_license_key');
        if (empty($encrypted_license_key)) {
            return new \WP_Error('license_not_found', 'License key not found.', array('status' => 404));
        }

        $license_key = $this->decrypt_license_key($encrypted_license_key);

        if ($license_key === false) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Failed to decrypt license key.'], 500);
        }

        if (empty($plugin_slug)) {
            return new \WP_Error('invalid_parameters', 'Plugin slug is required.', array('status' => 400));
        }

        // Vérifier si le plugin est déjà installé
        $installed_plugins = get_plugins();
        $plugin_installed = false;
        $plugin_path = null;

        foreach ($installed_plugins as $path => $plugin_data) {
            if (strpos($path, $plugin_slug . '/') === 0) {
                $plugin_installed = true;
                $plugin_path = $path;
                break;
            }
        }

        $short_slug       = $this->to_short_slug($plugin_slug);
        $is_license_local = $this->is_license_url_local();

        $api_url = add_query_arg([
            'license_key' => rawurlencode($license_key),
            'slug'        => rawurlencode($short_slug),
        ], ECWP_URL_LICENSE . '/wp-json/mec-license/v1/download');

        $response = wp_remote_get($api_url, array(
            'timeout'   => 60,
            'sslverify' => !$is_license_local,
        ));

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $error_code = $response->get_error_code();


            return new \WP_Error('api_error', 'Failed to connect to update API: ' . $error_message . ' (Code: ' . $error_code . ')', array('status' => 500));
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');


        // Vérifier si la réponse est un fichier ZIP (binaire) ou du JSON
        $is_zip = false;
        if (
            $content_type && (
                stripos($content_type, 'application/zip') !== false ||
                stripos($content_type, 'application/octet-stream') !== false ||
                stripos($content_type, 'binary') !== false ||
                stripos($content_type, 'zip') !== false
            )
        ) {
            $is_zip = true;
        }

        // Vérifier aussi par la signature du fichier ZIP (PK = 50 4B = 0x504B)
        if (!$is_zip && strlen($body) > 2) {
            $zip_signature = substr($body, 0, 2);
            if ($zip_signature === 'PK') {
                $is_zip = true;
            }
        }

        // Si c'est un fichier ZIP, le traiter directement
        if ($is_zip && !empty($body)) {

            // Créer un fichier temporaire
            $temp_file = wp_tempnam($plugin_slug . '.zip');

            if (!$temp_file) {
                return new \WP_Error('temp_file_failed', 'Failed to create temporary file.', array('status' => 500));
            }

            // Écrire le contenu dans le fichier temporaire
            $written = file_put_contents($temp_file, $body);

            if ($written === false || $written !== strlen($body)) {
                @unlink($temp_file);
                return new \WP_Error('write_failed', 'Failed to write downloaded file.', array('status' => 500));
            }


            // Utiliser directement le fichier téléchargé
            $data = array(
                'success' => true,
                'download_file' => $temp_file, // Fichier local au lieu d'URL
                'is_direct_file' => true
            );
        } else {
            // Sinon, essayer de parser comme JSON
            $data = json_decode($body, true);

            // Si la réponse n'est pas valide JSON ou est vide
            if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {

                return new \WP_Error('invalid_response', 'Invalid API response. Expected JSON or ZIP file. Response code: ' . $response_code . ', Content-Type: ' . $content_type, array('status' => 500));
            }

            if (!isset($data['success']) || !$data['success']) {
                $error_message = isset($data['message']) ? $data['message'] : 'Failed to download update.';
                return new \WP_Error('download_failed', $error_message, array('status' => $response_code));
            }

            // Si pas de download_url, retourner une erreur
            if (empty($data['download_url'])) {
                return new \WP_Error('no_download_url', 'Download URL not provided by API.', array('status' => 500));
            }
        }

        // Inclure les fichiers nécessaires pour l'installation
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

        // Créer un upgrader silencieux
        $upgrader = new \Plugin_Upgrader(new \WP_Ajax_Upgrader_Skin());

        // Si le plugin était déjà installé, on fait une mise à jour
        if ($plugin_installed && $plugin_path) {

            // Vérifier si on a déjà un fichier téléchargé directement
            if (isset($data['is_direct_file']) && $data['is_direct_file'] && isset($data['download_file'])) {
                $temp_file = $data['download_file'];
            } else {
                // Télécharger depuis l'URL

                $is_license_local = $this->is_license_url_local();

                $download_response = wp_remote_get($data['download_url'], array(
                    'timeout' => 300, // 5 minutes
                    'sslverify' => !$is_license_local,
                    'stream' => false,
                    'redirection' => 5,
                ));

                if (is_wp_error($download_response)) {
                    return new \WP_Error('download_failed', 'Failed to download plugin update: ' . $download_response->get_error_message(), array('status' => 500));
                }

                $response_code = wp_remote_retrieve_response_code($download_response);
                if ($response_code !== 200) {
                    return new \WP_Error('download_failed', 'Failed to download plugin update: HTTP ' . $response_code, array('status' => 500));
                }

                $file_content = wp_remote_retrieve_body($download_response);

                if (empty($file_content)) {
                    return new \WP_Error('download_failed', 'Downloaded file is empty.', array('status' => 500));
                }


                // Créer un fichier temporaire
                $temp_file = wp_tempnam($plugin_slug . '-update.zip');

                if (!$temp_file) {
                    return new \WP_Error('temp_file_failed', 'Failed to create temporary file.', array('status' => 500));
                }

                // Écrire le contenu dans le fichier temporaire
                $written = file_put_contents($temp_file, $file_content);

                if ($written === false || $written !== strlen($file_content)) {
                    @unlink($temp_file);
                    return new \WP_Error('write_failed', 'Failed to write downloaded file.', array('status' => 500));
                }

            }

            // Faire la mise à jour depuis le fichier local
            $upgrade_result = $upgrader->upgrade($temp_file);

            // Nettoyer le fichier temporaire
            @unlink($temp_file);

            if (is_wp_error($upgrade_result)) {
                return new \WP_Error('update_failed', 'Plugin update failed: ' . $upgrade_result->get_error_message(), array('status' => 500));
            }

            // Activer le plugin s'il n'est pas déjà activé (après la mise à jour)
            if (!is_plugin_active($plugin_path)) {
                $activate_result = activate_plugin($plugin_path);
                if (is_wp_error($activate_result)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                    }
                }
            }

            return new \WP_REST_Response(array(
                'success' => true,
                'message' => 'Plugin updated successfully.',
                'action' => 'updated'
            ), 200);
        } else {
            // Nouvelle installation - télécharger et installer le plugin
            // Log de l'URL de téléchargement (toujours loguer pour débogage)
            // Nouvelle installation

            // Télécharger le fichier manuellement car download_url() ne gère pas les URLs avec tokens
            $is_license_local = $this->is_license_url_local();

            $download_response = wp_remote_get($data['download_url'], array(
                'timeout' => 300, // 5 minutes
                'sslverify' => !$is_license_local,
                'stream' => false, // Télécharger en mémoire d'abord
                'redirection' => 5,
            ));

            if (is_wp_error($download_response)) {
                return new \WP_Error('download_failed', 'Failed to download plugin: ' . $download_response->get_error_message(), array('status' => 500));
            }

            $response_code = wp_remote_retrieve_response_code($download_response);
            if ($response_code !== 200) {
                return new \WP_Error('download_failed', 'Failed to download plugin: HTTP ' . $response_code, array('status' => 500));
            }

            $file_content = wp_remote_retrieve_body($download_response);

            if (empty($file_content)) {
                return new \WP_Error('download_failed', 'Downloaded file is empty.', array('status' => 500));
            }


            // Créer un fichier temporaire
            $temp_file = wp_tempnam($plugin_slug . '.zip');

            if (!$temp_file) {
                return new \WP_Error('temp_file_failed', 'Failed to create temporary file.', array('status' => 500));
            }

            // Écrire le contenu dans le fichier temporaire
            $written = file_put_contents($temp_file, $file_content);

            if ($written === false || $written !== strlen($file_content)) {
                @unlink($temp_file);
                return new \WP_Error('write_failed', 'Failed to write downloaded file.', array('status' => 500));
            }


            // Installer depuis le fichier local
            $install_result = $upgrader->install($temp_file);

            // Nettoyer le fichier temporaire
            @unlink($temp_file);

            if (is_wp_error($install_result)) {
                return new \WP_Error('install_failed', 'Plugin installation failed: ' . $install_result->get_error_message(), array('status' => 500));
            }

            // $install_result peut être true, un array avec des infos, ou false
            // Examiner le résultat en détail
            $install_success = false;

            if ($install_result === true) {
                $install_success = true;
            } elseif (is_array($install_result)) {
                // Le Plugin_Upgrader peut retourner un array avec des informations
                // Vérifier si l'installation a réussi en regardant les clés du résultat
                if (isset($install_result['destination_name']) || isset($install_result['destination'])) {
                    $install_success = true;
                } else {
                    // Vérifier s'il y a des messages d'erreur dans le résultat
                    if (isset($install_result['errors']) && !empty($install_result['errors'])) {
                        return new \WP_Error('install_failed', 'Plugin installation failed: ' . print_r($install_result['errors'], true), array('status' => 500));
                    }
                    // Si pas d'erreurs explicites, considérer comme succès
                    $install_success = true;
                }
            } else {
                // Si ce n'est ni true ni un array, c'est probablement un échec
                return new \WP_Error('install_failed', 'Plugin installation failed: Unexpected result type.', array('status' => 500));
            }

            // Vérifier que l'installation a vraiment réussi en vérifiant le système de fichiers
            $plugins_dir = WP_PLUGIN_DIR;
            $plugin_dir_path = $plugins_dir . '/' . $plugin_slug;


            // Si le dossier n'existe pas, l'installation a probablement échoué
            if (!is_dir($plugin_dir_path)) {
                // Essayer de trouver le plugin avec un nom légèrement différent
                $all_plugin_dirs = glob($plugins_dir . '/*', GLOB_ONLYDIR);
                $found_dir = null;

                foreach ($all_plugin_dirs as $dir) {
                    $dir_name = basename($dir);
                    // Chercher des variations du slug
                    if (stripos($dir_name, $plugin_slug) !== false || stripos($plugin_slug, $dir_name) !== false) {
                        $found_dir = $dir;
                        break;
                    }
                }

                if (!$found_dir) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                    }
                    return new \WP_Error('install_failed', 'Plugin installation failed: Plugin directory not found after installation. Please check file permissions and try again.', array('status' => 500));
                }

                $plugin_dir_path = $found_dir;
                $plugin_slug = basename($found_dir);

                if (defined('WP_DEBUG') && WP_DEBUG) {
                }
            }

            // Vérifier qu'il y a au moins un fichier PHP dans le dossier
            $plugin_files = glob($plugin_dir_path . '/*.php');
            if (empty($plugin_files)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                }
                return new \WP_Error('install_failed', 'Plugin installation failed: No plugin files found in directory.', array('status' => 500));
            }

            // Forcer le rafraîchissement du cache des plugins
            wp_cache_delete('plugins', 'plugins');
            if (function_exists('delete_plugins_cache')) {
                delete_plugins_cache();
            }
            // Nettoyer le cache de transients WordPress
            wp_cache_flush();

            // Nouvelle installation - trouver le chemin du plugin installé
            // Attendre un peu pour que WordPress mette à jour sa liste
            sleep(1);

            $installed_plugins_after = get_plugins();
            $new_plugin_path = null;

            // Méthode 1: Chercher par slug exact dans le chemin
            foreach ($installed_plugins_after as $path => $plugin_data) {
                if (strpos($path, $plugin_slug . '/') === 0) {
                    $new_plugin_path = $path;
                    break;
                }
            }

            // Méthode 2: Si pas trouvé, chercher par nom de dossier (comme dans le reste du code)
            if (!$new_plugin_path) {
                foreach ($installed_plugins_after as $path => $plugin_data) {
                    $dir_slug = dirname(plugin_basename($path));
                    if ($dir_slug === $plugin_slug) {
                        $new_plugin_path = $path;
                        break;
                    }
                }
            }

            // Méthode 3: Chercher par nom du plugin (plus flexible)
            if (!$new_plugin_path && isset($data['plugin_name'])) {
                foreach ($installed_plugins_after as $path => $plugin_data) {
                    if (isset($plugin_data['Name']) && stripos($plugin_data['Name'], $data['plugin_name']) !== false) {
                        $new_plugin_path = $path;
                        break;
                    }
                }
            }

            // Méthode 4: Construire le chemin directement depuis le système de fichiers
            if (!$new_plugin_path) {
                $main_plugin_file = $plugin_dir_path . '/' . $plugin_slug . '.php';
                if (!file_exists($main_plugin_file)) {
                    // Chercher le premier fichier PHP dans le dossier
                    $main_plugin_file = $plugin_files[0];
                }

                if (file_exists($main_plugin_file)) {
                    $relative_path = str_replace($plugins_dir . '/', '', $main_plugin_file);
                    if (isset($installed_plugins_after[$relative_path])) {
                        $new_plugin_path = $relative_path;
                    }
                }
            }


            if ($new_plugin_path) {
                // Activer le plugin
                $activate_result = activate_plugin($new_plugin_path);

                if (is_wp_error($activate_result)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                    }
                    // L'installation a réussi mais l'activation a échoué
                    return new \WP_REST_Response(array(
                        'success' => true,
                        'message' => 'Plugin installed successfully but activation failed. Please activate manually.',
                        'action' => 'installed',
                        'activation_failed' => true,
                        'plugin_path' => $new_plugin_path
                    ), 200);
                }

                return new \WP_REST_Response(array(
                    'success' => true,
                    'message' => 'Plugin installed and activated successfully.',
                    'action' => 'installed',
                    'plugin_path' => $new_plugin_path
                ), 200);
            } else {

                return new \WP_Error('plugin_not_detected', 'Plugin files were installed but WordPress could not detect them. Please check the plugin directory structure and refresh the plugins page manually.', array(
                    'status' => 500,
                    'plugin_directory' => $plugin_dir_path,
                    'plugin_slug' => $plugin_slug
                ));
            }
        }
    }

    /**
     * Ajoute un domaine autorisé à la licence
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function add_domain(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }

        $encrypted_license_key = get_option('ecwp_client_license_key');
        if (empty($encrypted_license_key)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'License key not found.'], 404);
        }

        $license_key = $this->decrypt_license_key($encrypted_license_key);

        if ($license_key === false) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Failed to decrypt license key.'], 500);
        }

        $domain = sanitize_text_field($request->get_param('domain'));

        if (empty($domain)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Domain is required.'], 400);
        }

        // Normaliser le domaine
        $domain = $this->normalize_domain($domain);

        // Appel à l'API pour ajouter le domaine
        $api_url = ECWP_URL_LICENSE . '/wp-json/mlz-license/v1/add-domain';
        $is_license_local = $this->is_license_url_local();

        $response = wp_remote_post($api_url, [
            'body' => wp_json_encode([
                'license_key' => $license_key,
                'domain' => $domain,
            ]),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 15,
            'sslverify' => !$is_license_local, // Désactiver la vérification SSL si l'URL de licence est locale
        ]);

        if (is_wp_error($response)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Failed to connect to license server: ' . $response->get_error_message(),
            ], 500);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $response_code = wp_remote_retrieve_response_code($response);

        // Si le domaine a été ajouté avec succès, mettre à jour les données de licence locales
        if ($response_code === 200 && isset($data['success']) && $data['success']) {
            // Rafraîchir les données de licence
            $license_data = $this->get_validate_license($license_key);
            if ($license_data && isset($license_data['valid']) && $license_data['valid']) {
                update_option('ecwp_client_license_data', $license_data);
            }
        }

        return new \WP_REST_Response($data, $response_code);
    }

    /**
     * Supprime un domaine autorisé de la licence
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function remove_domain(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', array('status' => 403));
        }

        $encrypted_license_key = get_option('ecwp_client_license_key');
        if (empty($encrypted_license_key)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'License key not found.'], 404);
        }

        $license_key = $this->decrypt_license_key($encrypted_license_key);

        if ($license_key === false) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Failed to decrypt license key.'], 500);
        }

        $domain = sanitize_text_field($request->get_param('domain'));

        if (empty($domain)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Domain is required.'], 400);
        }

        // Normaliser le domaine
        $domain = $this->normalize_domain($domain);

        // Appel à l'API pour supprimer le domaine
        $api_url = ECWP_URL_LICENSE . '/wp-json/mlz-license/v1/remove-domain';
        $is_license_local = $this->is_license_url_local();

        $response = wp_remote_post($api_url, [
            'body' => wp_json_encode([
                'license_key' => $license_key,
                'domain' => $domain,
            ]),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 15,
            'sslverify' => !$is_license_local, // Désactiver la vérification SSL si l'URL de licence est locale
        ]);

        if (is_wp_error($response)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Failed to connect to license server: ' . $response->get_error_message(),
            ], 500);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $response_code = wp_remote_retrieve_response_code($response);

        // Si le domaine a été supprimé avec succès, mettre à jour les données de licence locales
        if ($response_code === 200 && isset($data['success']) && $data['success']) {
            // Rafraîchir les données de licence
            $license_data = $this->get_validate_license($license_key);
            if ($license_data && isset($license_data['valid']) && $license_data['valid']) {
                update_option('ecwp_client_license_data', $license_data);
            }
        }

        return new \WP_REST_Response($data, $response_code);
    }

    public function register_affiliate(\WP_REST_Request $request)
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce verification failed.', ['status' => 403]);
        }

        $body          = (array) $request->get_json_params();
        $name          = sanitize_text_field($body['name']          ?? '');
        $email         = sanitize_email($body['email']              ?? '');
        $website       = esc_url_raw($body['website']               ?? '');
        $payment_email = sanitize_email($body['payment_email']      ?? $email);
        $message       = sanitize_textarea_field($body['message']   ?? '');

        if (!$name || !is_email($email)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Nom et email requis.'], 400);
        }

        // Include current license key automatically
        $encrypted = get_option('ecwp_client_license_key', '');
        $license_key = $encrypted ? ($this->decrypt_license_key($encrypted) ?: '') : '';

        $is_local = $this->is_license_url_local();
        $api_url  = ECWP_URL_LICENSE . '/wp-json/mec-license/v1/affiliate/apply';

        $response = wp_remote_post($api_url, [
            'body'      => wp_json_encode([
                'name'          => $name,
                'email'         => $email,
                'website'       => $website,
                'payment_email' => $payment_email,
                'license_key'   => $license_key,
                'message'       => $message,
            ]),
            'headers'   => ['Content-Type' => 'application/json'],
            'timeout'   => 15,
            'sslverify' => !$is_local,
        ]);

        if (is_wp_error($response)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Impossible de contacter le serveur.'], 500);
        }

        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        return new \WP_REST_Response($data ?? ['success' => false], $code);
    }

    public function send_test_email(\WP_REST_Request $request): \WP_REST_Response
    {
        $nonce = sanitize_text_field(wp_unslash($request->get_header('X-WP-Nonce')));
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Nonce invalide.'], 403);
        }

        $admin_email = get_option('admin_email');
        if (!$admin_email) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Aucun email admin configuré dans WordPress.'], 200);
        }

        $theme = sanitize_key($request->get_param('theme') ?? 'dark');
        if (!in_array($theme, ['dark', 'light'], true)) $theme = 'dark';

        $body = \ECWP\Admin\EmailTemplate::render('invoice_paid', [
            'invoice_number' => 'INV-TEST',
            'client_name'    => 'Test Client',
            'view_url'       => admin_url('admin.php?page=my-easy-compta#/invoices'),
        ], $theme);

        $failed_reason = '';
        add_action('wp_mail_failed', function (\WP_Error $err) use (&$failed_reason) {
            $failed_reason = implode(', ', $err->get_error_messages());
        });

        $sent = wp_mail(
            $admin_email,
            '🧪 Test email — myEasyCompta',
            $body ?: '<p>Test email depuis myEasyCompta.</p>',
            ['Content-Type: text/html; charset=UTF-8']
        );

        if ($sent) {
            return new \WP_REST_Response([
                'success' => true,
                'message' => "Email envoyé à {$admin_email}. Vérifiez votre boîte mail ou MailHog (Local).",
            ], 200);
        }

        return new \WP_REST_Response([
            'success' => false,
            'message' => $failed_reason ?: "wp_mail() a retourné false. Vérifiez la configuration SMTP ou MailHog.",
            'to'      => $admin_email,
        ], 200);
    }

    public function preview_email(\WP_REST_Request $request): \WP_REST_Response
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'Modules/EmailTemplate.php';

        $type  = sanitize_key($request->get_param('type')  ?? 'quote_accepted');
        $theme = sanitize_key($request->get_param('theme') ?? 'dark');
        if (!in_array($theme, ['dark', 'light'], true)) $theme = 'dark';

        $data = [
            'quote_number' => 'EST-0001',
            'client_name'  => 'Amazon SAS',
            'comment'      => 'Ok, c\'est parfait pour nous.',
            'view_url'     => admin_url('admin.php?page=my-easy-compta#/quotes/detail/1'),
        ];

        $html = \ECWP\Admin\EmailTemplate::render($type, $data, $theme);

        return new \WP_REST_Response(['html' => $html], 200);
    }

}
