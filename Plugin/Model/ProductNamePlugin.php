<?php
declare(strict_types=1);

namespace MW\Pokemon\Plugin\Model;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Request\Http;
use MW\Pokemon\Api\Config\ProviderInterface;
use MW\Pokemon\Api\Data\PokemonDataProviderInterface;

class ProductNamePlugin
{
    /**
     * @param ProviderInterface $configPovider
     * @param PokemonDataProviderInterface $pokemonDataProvider
     * @param Http $request
     */
    public function __construct(
        private readonly ProviderInterface $configPovider,
        private readonly PokemonDataProviderInterface $pokemonDataProvider,
        private readonly Http $request
    ) {
    }

    /**
     *
     * @param Product $subject
     * @param string $result
     * @return string
     */
    public function afterGetName(Product $subject, string $result): string
    {
        if (in_array($this->request->getFullActionName(), ['catalog_product_view','catalog_category_view'])) {
            $pokemonName = $subject->getPokemonName();
            if ($this->configPovider->pokemonModuleIsEnabled() && !empty($pokemonName)) {
                $pokemonData = $this->pokemonDataProvider->getPokemonData($pokemonName);
                if (array_key_exists('name', $pokemonData)) {
                    $suffix = ' - ' . $pokemonData['name'];
                    $result .= $suffix;
                }
            }
        }

        return $result;
    }
}
