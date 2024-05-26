<?php
declare(strict_types=1);

namespace MW\Pokemon\Plugin\Block\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Image;
use Magento\Framework\App\Request\Http;
use MW\Pokemon\Api\Config\ProviderInterface;
use MW\Pokemon\Api\Data\PokemonDataProviderInterface;

class ProductThumbnailPlugin
{
    /**
     * @param ProviderInterface $configProvider
     * @param PokemonDataProviderInterface $pokemonDataProvider
     * @param Http $request
     */
    public function __construct(
        private readonly ProviderInterface $configProvider,
        private readonly PokemonDataProviderInterface $pokemonDataProvider,
        private readonly Http$request
    ) {

    }
    /**
     *
     * @param AbstractProduct $subject
     * @param Image $result
     * @param ProductInterface $product
     * @return Image
     */
    public function afterGetImage(AbstractProduct $subject, Image $result, ProductInterface $product): Image
    {
        if (in_array($this->request->getFullActionName(), ['catalog_product_view', 'catalog_category_view'])) {
            $pokemonName = $product->getPokemonName();
            if ($this->configProvider->pokemonModuleIsEnabled() && !empty($pokemonName)) {
                $pokemonData = $this->pokemonDataProvider->getPokemonData($pokemonName);
                if (array_key_exists('image', $pokemonData)) {
                    $customImageUrl = $pokemonData['image'];
                    $result->setImageUrl($customImageUrl);
                }
            }
        }
        return $result;
    }
}
