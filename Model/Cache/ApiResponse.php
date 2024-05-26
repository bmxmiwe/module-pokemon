<?php
declare(strict_types=1);

namespace MW\Pokemon\Model\Cache;

use Magento\Framework\Cache\Frontend\Decorator\TagScope;
use Magento\Framework\App\Cache\Type\FrontendPool;

class ApiResponse extends TagScope
{
    public const TYPE_IDENTIFIER = 'pokemon_api_response_cache';
    public const CACHE_TAG = 'POKEMON_API_RESPONSE_CACHE';

    /**
     * @param FrontendPool $cacheFrontendPool
     */
    public function __construct(FrontendPool $cacheFrontendPool)
    {
        parent::__construct(
            $cacheFrontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );
    }
}
