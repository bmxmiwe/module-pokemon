<?php
declare(strict_types=1);

namespace MW\Pokemon\Plugin\Block\Product\View;

use Magento\Catalog\Block\Product\View\Gallery;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Serialize\Serializer\Json;
use MW\Pokemon\Api\Config\ProviderInterface;
use MW\Pokemon\Api\Data\PokemonDataProviderInterface;

class ProductGalleryPlugin
{
    /**
     * @param ProviderInterface $configPovider
     * @param PokemonDataProviderInterface $pokemonDataProvider
     * @param Http $request
     */
    public function __construct(
        private readonly ProviderInterface $configPovider,
        private readonly PokemonDataProviderInterface $pokemonDataProvider,
        private readonly Http $request,
        private readonly Json $jsonSerializer
    ) {
    }

    /**
     * Modify the base image URL for the product.
     *
     * @param Gallery $subject
     * @param string $result
     * @return string
     */
    public function afterGetGalleryImagesJson(Gallery $subject, string $result): string
    {
        if ($this->request->getFullActionName() === 'catalog_product_view') {
            try {
                $images = $this->jsonSerializer->unserialize($result);
            } catch (\Exception $e) {

                return $result;
            }
            $pokemonName = $subject->getProduct()->getPokemonName();
            if ($this->configPovider->pokemonModuleIsEnabled() && !empty($pokemonName)) {
                $pokemonData = $this->pokemonDataProvider->getPokemonData($pokemonName);
                if (array_key_exists('image', $pokemonData) && is_array($images) && !empty($images)) {
                    foreach ($images as &$image) {
                        if ($image['isMain'] === true) {
                            $image['thumb'] = $pokemonData['image'];
                            $image['img'] = $pokemonData['image'];
                            $image['full'] = $pokemonData['image'];
                        }
                    }
                }
            }
        }

        return (empty($images)) ? $result : $this->jsonSerializer->serialize($images);
    }
}
