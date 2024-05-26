<?php
declare(strict_types=1);

namespace MW\Pokemon\Model;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Message\ManagerInterface;
use MW\Pokemon\Api\Data\PokemonDataProviderInterface;
use MW\Pokemon\Api\PokemonCacheProviderInterface;
use MW\Pokemon\Model\Config\Provider;
use Psr\Log\LoggerInterface;

class PokemonDataProvider implements PokemonDataProviderInterface
{
    /**
     * @param PokemonCacheProviderInterface $cacheProvider
     * @param Client $client
     * @param LoggerInterface $logger
     * @param Provider $configProvider
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        private readonly PokemonCacheProviderInterface $cacheProvider,
        private readonly Client $client,
        private readonly LoggerInterface $logger,
        private readonly Provider $configProvider,
        private readonly ManagerInterface $messageManager
    ) {
    }

    /**
     * Method is getting pokemon data from cache, if not exists, it will be downloaded from api
     *
     * @param string $pokemonName
     * @return array
     */
    public function getPokemonData(string $pokemonName): array
    {
        $apiResponse = [];
        $cachedResponse = $this->cacheProvider->loadResponse($pokemonName);
        if ($cachedResponse) {
            $apiResponse = $cachedResponse;
        } else {
            $apiResponseContent = $this->getApiResponse($pokemonName);
            if ($apiResponseContent === null) {

                return $apiResponse;
            }
            $apiResponseContent = json_decode($apiResponseContent);
            if (property_exists($apiResponseContent, 'name')) {
                $apiResponse['name'] = $apiResponseContent->name;
            } else {
                $this->messageManager->addErrorMessage(__('Can\'t get pokemon name.'));
            }
            if (property_exists($apiResponseContent, 'sprites')
                && property_exists($apiResponseContent->sprites, 'front_default')) {
                $apiResponse['image'] = $apiResponseContent->sprites->front_default;
            } else {
                $this->messageManager->addErrorMessage(__('Can\'t get pokemon image.'));
            }
            $this->cacheProvider->saveResponse($pokemonName, $apiResponse);
        }

        return $apiResponse;
    }

    /**
     *
     * @param string $pokemonName
     * @return string|null
     */
    private function getApiResponse(string $pokemonName): null|string
    {
        try {
            $response = $this->client->request('GET', $this->configProvider->getApiUrl() . '/' . $pokemonName);
            if ($response->getStatusCode() == 200) {
                $apiResponseContent = $response->getBody()->getContents();
            } else {
                $this->logger->error('API request failed with status code: ' . $response->getStatusCode());
                $this->messageManager->addErrorMessage(__('Can\'t get pokemon data.'));

                return null;
            }
        } catch (GuzzleException $e) {
            $this->logger->error('API request failed with exception: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('Can\'t get pokemon data.'));

            return null;
        }

        return $apiResponseContent;
    }
}
