<?php
declare(strict_types=1);

namespace MW\Pokemon\Test\Unit\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use MW\Pokemon\Model\Config\Provider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProviderTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Provider
     */
    private $provider;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->provider = new Provider($this->scopeConfigMock);
    }

    /**
     * @return void
     */
    public function testPokemonModuleIsEnabled(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(Provider::XML_PATH_TO_POKEMON_MODULE_IS_ENABLED)
            ->willReturn(true);

        $this->assertTrue($this->provider->pokemonModuleIsEnabled());
    }

    /**
     * @return void
     */
    public function testGetApiUrl(): void
    {
        $apiUrl = 'https://api.example.com/pokemon';

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(Provider::XML_PATH_TO_POKEMON_MODULE_API_URL)
            ->willReturn($apiUrl);

        $this->assertEquals($apiUrl, $this->provider->getApiUrl());
    }
}
