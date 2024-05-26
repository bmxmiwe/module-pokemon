<?php
declare(strict_types=1);

namespace MW\Pokemon\Test\Unit\Model;

use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\Cache\FrontendInterface;
use Magento\Framework\Serialize\SerializerInterface;
use MW\Pokemon\Model\PokemonCacheProvider;
use MW\Pokemon\Model\Cache\ApiResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PokemonCacheProviderTest extends TestCase
{
    /**
     * @var FrontendInterface|MockObject
     */
    private $cacheMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var PokemonCacheProvider
     */
    private $pokemonCacheProvider;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->cacheMock = $this->createMock(FrontendInterface::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $cacheFrontendPoolMock = $this->createMock(Pool::class);
        $cacheFrontendPoolMock->method('get')
            ->with(ApiResponse::TYPE_IDENTIFIER)
            ->willReturn($this->cacheMock);

        $this->pokemonCacheProvider = new PokemonCacheProvider(
            $cacheFrontendPoolMock,
            $this->serializerMock
        );
    }

    /**
     * @return void
     */
    public function testSaveResponse(): void
    {
        $pokemonName = 'pikachu';
        $response = ['name' => 'Pikachu', 'image' => 'http://pika.com/pikaczu'];

        $cacheKey = ApiResponse::CACHE_TAG . '_' . $pokemonName;

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($response)
            ->willReturn(json_encode($response));

        $this->cacheMock->expects($this->once())
            ->method('save')
            ->with(
                json_encode($response),
                $cacheKey,
                [ApiResponse::CACHE_TAG],
                PokemonCacheProvider::CACHE_LIFETIME
            );

        $this->pokemonCacheProvider->saveResponse($pokemonName, $response);
    }

    /**
     * @return void
     */
    public function testLoadResponse(): void
    {
        $pokemonName = 'pikachu';
        $cacheKey = ApiResponse::CACHE_TAG . '_' . $pokemonName;
        $cachedData = json_encode(['name' => 'Pikachu', 'image' => 'http://pika.com/pikaczu']);

        $this->cacheMock->expects($this->once())
            ->method('load')
            ->with($cacheKey)
            ->willReturn($cachedData);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($cachedData)
            ->willReturn(['name' => 'Pikachu', 'image' => 'http://pika.com/pikaczu']);

        $result = $this->pokemonCacheProvider->loadResponse($pokemonName);

        $this->assertEquals(['name' => 'Pikachu', 'image' => 'http://pika.com/pikaczu'], $result);
    }

    /**
     * @return void
     */
    public function testLoadResponseReturnsNullWhenNoCache(): void
    {
        $pokemonName = 'pikachu';
        $cacheKey = ApiResponse::CACHE_TAG . '_' . $pokemonName;

        $this->cacheMock->expects($this->once())
            ->method('load')
            ->with($cacheKey)
            ->willReturn(false);

        $this->serializerMock->expects($this->never())
            ->method('unserialize');

        $result = $this->pokemonCacheProvider->loadResponse($pokemonName);

        $this->assertNull($result);
    }
}
