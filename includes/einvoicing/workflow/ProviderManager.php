<?php

namespace ECWP\EInvoicing\Workflow;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ProviderManager
 * 
 * Gère l'enregistrement et la récupération des connecteurs PDP.
 */
class ProviderManager
{
    private static $instance = null;
    private $providers = [];

    private function __construct() {}

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Enregistre un provider PDP.
     * Cette méthode est appelée par les addons.
     * 
     * @param PDPInterface $provider
     */
    public function registerProvider(PDPInterface $provider)
    {
        $this->providers[$provider->getId()] = $provider;
    }

    /**
     * Récupère tous les providers enregistrés
     * 
     * @return PDPInterface[]
     */
    public function getProviders(): array
    {
        do_action('myeasycompta_register_einvoice_providers', $this);

        $filtered = apply_filters('myeasycompta_einvoice_providers', []);
        if (is_array($filtered)) {
            foreach ($filtered as $provider) {
                if ($provider instanceof PDPInterface) {
                    $this->registerProvider($provider);
                }
            }
        }

        return $this->providers;
    }

    /**
     * Récupère un provider spécifique par son ID
     * 
     * @param string $id
     * @return PDPInterface|null
     */
    public function getProvider(string $id): ?PDPInterface
    {
        return $this->providers[$id] ?? null;
    }

    /**
     * Récupère le provider actif (celui choisi dans les réglages)
     * 
     * @return PDPInterface|null
     */
    public function getActiveProvider(): ?PDPInterface
    {
        $providers = $this->getProviders();

        $activeId = get_option('myeasycompta_active_pdp_provider');
        if (!$activeId) {
            $activeId = get_option('ecwp_active_pdp_provider');
        }

        return $activeId && isset($providers[$activeId]) ? $providers[$activeId] : null;
    }
}
