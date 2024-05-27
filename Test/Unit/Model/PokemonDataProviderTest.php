<?php
declare(strict_types=1);

namespace MW\Pokemon\Test\Unit\Model;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use MW\Pokemon\Api\PokemonCacheProviderInterface;
use MW\Pokemon\Model\Config\Provider;
use MW\Pokemon\Model\PokemonDataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class PokemonDataProviderTest extends TestCase
{
    /**
     * @var MockObject|PokemonCacheProviderInterface
     */
    private $cacheProviderMock;

    /**
     * @var MockObject|Client
     */
    private $clientMock;

    /**
     * @var MockObject|LoggerInterface
     */
    private $loggerMock;

    /**
     * @var MockObject|Provider
     */
    private $configProviderMock;

    /**
     * @var MockObject|ManagerInterface
     */
    private $messageManagerMock;

    /**
     * @var MockObject|Json
     */
    private $jsonSerializerMock;

    /**
     * @var PokemonDataProvider
     */
    private $pokemonDataProvider;

    /**
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->cacheProviderMock = $this->createMock(PokemonCacheProviderInterface::class);
        $this->clientMock = $this->createMock(Client::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->configProviderMock = $this->createMock(Provider::class);
        $this->messageManagerMock = $this->createMock(ManagerInterface::class);
        $this->jsonSerializerMock = $this->createMock(Json::class);

        $this->pokemonDataProvider = new PokemonDataProvider(
            $this->cacheProviderMock,
            $this->clientMock,
            $this->loggerMock,
            $this->configProviderMock,
            $this->messageManagerMock,
            $this->jsonSerializerMock
        );
    }

    /**
     *
     * @return void
     */
    public function testGetPokemonDataReturnsCachedData(): void
    {
        $pokemonName = 'pikachu';
        $cachedData = ['name' => 'Pikachu', 'image' => 'http://poke.go/pika'];

        $this->cacheProviderMock->expects($this->once())
            ->method('loadResponse')
            ->with($pokemonName)
            ->willReturn($cachedData);

        $result = $this->pokemonDataProvider->getPokemonData($pokemonName);

        $this->assertEquals($cachedData, $result);
    }

    /**
     *
     * @return void
     */
    public function testGetPokemonDataReturnsApiDataAndCachesIt(): void
    {
        $pokemonName = 'pikachu';
        $apiResponse = '{"name":"Pikachu","sprites":{"front_default":"image_url"}}';
        $apiUrl = 'https://api.example.com/pokemon';

        $this->cacheProviderMock->expects($this->once())
            ->method('loadResponse')
            ->with($pokemonName)
            ->willReturn(null);

        $this->configProviderMock->expects($this->once())
            ->method('getApiUrl')
            ->willReturn($apiUrl);

        $responseMock = $this->createMock(Response::class);
        $responseMock->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($this->createStream($apiResponse));

        $this->clientMock->expects($this->once())
            ->method('request')
            ->with('GET', $apiUrl . '/' . $pokemonName)
            ->willReturn($responseMock);

        $this->jsonSerializerMock->expects($this->once())
            ->method('unserialize')
            ->with($apiResponse)
            ->willReturn(json_decode($apiResponse, true));

        $this->cacheProviderMock->expects($this->once())
            ->method('saveResponse')
            ->with($pokemonName, ['name' => 'Pikachu', 'image' => 'image_url']);

        $result = $this->pokemonDataProvider->getPokemonData($pokemonName);

        $this->assertEquals(['name' => 'Pikachu', 'image' => 'image_url'], $result);
    }

    /**
     *
     * @return void
     */
    public function testGetPokemonDataHandlesApiException(): void
    {
        $pokemonName = 'pikachu';
        $apiUrl = 'https://api.example.com/pokemon';

        $this->cacheProviderMock->expects($this->once())
            ->method('loadResponse')
            ->with($pokemonName)
            ->willReturn(null);

        $this->configProviderMock->expects($this->once())
            ->method('getApiUrl')
            ->willReturn($apiUrl);

        $request = new Request('GET', $apiUrl . '/' . $pokemonName);
        $response = new Response(404, [], 'Not Found');
        $this->clientMock->expects($this->once())
            ->method('request')
            ->with('GET', $apiUrl . '/' . $pokemonName)
            ->willThrowException(new ClientException('Not Found', $request, $response));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('API request failed with exception: Not Found');

        $this->messageManagerMock->expects($this->once())
            ->method('addErrorMessage')
            ->with(__('Can\'t get pokemon data.'));

        $result = $this->pokemonDataProvider->getPokemonData($pokemonName);

        $this->assertEquals([], $result);
    }

    /**
     *
     * @return void
     */
    public function testGetPokemonDataHandlesJsonException(): void
    {
        $pokemonName = 'pikachu';
        $apiResponse = '{"name":"Pikachu","sprites":{"front_default":"image_url"}}';
        $apiUrl = 'https://api.example.com/pokemon';

        $this->cacheProviderMock->expects($this->once())
            ->method('loadResponse')
            ->with($pokemonName)
            ->willReturn(null);

        $this->configProviderMock->expects($this->once())
            ->method('getApiUrl')
            ->willReturn($apiUrl);

        $responseMock = $this->createMock(Response::class);
        $responseMock->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($this->createStream($apiResponse));

        $this->clientMock->expects($this->once())
            ->method('request')
            ->with('GET', $apiUrl . '/' . $pokemonName)
            ->willReturn($responseMock);

        $this->jsonSerializerMock->expects($this->once())
            ->method('unserialize')
            ->with($apiResponse)
            ->willThrowException(new \Exception('Invalid JSON'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Wrong API response content. Invalid JSON');

        $this->messageManagerMock->expects($this->once())
            ->method('addErrorMessage')
            ->with(__('Can\'t get pokemon data.'));

        $result = $this->pokemonDataProvider->getPokemonData($pokemonName);

        $this->assertEquals([], $result);
    }

    /**
     *
     * @param string $content
     * @return MockObject|(StreamInterface&MockObject)
     */
    private function createStream(string $content)
    {
        $stream = $this->getMockBuilder(StreamInterface::class)
            ->getMock();
        $stream->expects($this->once())
            ->method('getContents')
            ->willReturn($content);

        return $stream;
    }
}
