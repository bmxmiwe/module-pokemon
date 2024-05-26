<?php
declare(strict_types=1);

namespace MW\Pokemon\Model;

use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\Cache\FrontendInterface;
use Magento\Framework\Serialize\SerializerInterface;
use MW\Pokemon\Api\PokemonCacheProviderInterface;
use MW\Pokemon\Model\Cache\ApiResponse;

class PokemonCacheProvider implements PokemonCacheProviderInterface
{
    /**
     * @var FrontendInterface
     */
    private FrontendInterface $cache;
    public const CACHE_LIFETIME = 3600;

    /**
     * @param Pool $cacheFrontendPool
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Pool                $cacheFrontendPool,
        private readonly SerializerInterface $serializer
    ) {
        $this->cache = $cacheFrontendPool->get(ApiResponse::TYPE_IDENTIFIER);
    }

    /**
     *
     * @param string $pokemonName
     * @param array $response
     * @return void
     */
    public function saveResponse(string $pokemonName, array $response): void
    {
        $cacheKey = $this->getCacheKey($pokemonName);
        $this->cache->save(
            $this->serializer->serialize($response),
            $cacheKey,
            [ApiResponse::CACHE_TAG],
            self::CACHE_LIFETIME
        );
    }

    /**
     *
     * @param string $pokemonName
     * @return array|null
     */
    public function loadResponse(string $pokemonName): ?array
    {
        $cacheKey = $this->getCacheKey($pokemonName);
        $cachedData = $this->cache->load($cacheKey);

        return $cachedData ? $this->serializer->unserialize($cachedData) : null;
    }

    /**
     *
     * @param string $pokemonName
     * @return string
     */
    protected function getCacheKey(string $pokemonName): string
    {
        return ApiResponse::CACHE_TAG. '_' . $pokemonName;
    }
}
