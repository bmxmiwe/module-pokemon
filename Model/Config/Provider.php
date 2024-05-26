<?php
declare(strict_types=1);

namespace MW\Pokemon\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use MW\Pokemon\Api\Config\ProviderInterface;

class Provider implements ProviderInterface
{
    public const XML_PATH_TO_POKEMON_MODULE_IS_ENABLED = 'pokemon_integration/settings/enabled';
    public const XML_PATH_TO_POKEMON_MODULE_API_URL = 'pokemon_integration/settings/api_url';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     *
     * @return bool
     */
    public function pokemonModuleIsEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_TO_POKEMON_MODULE_IS_ENABLED);
    }

    /**
     *
     * @return string
     */
    public function getApiUrl(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_TO_POKEMON_MODULE_API_URL);
    }
}
