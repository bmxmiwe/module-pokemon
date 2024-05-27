<?php
declare(strict_types=1);

namespace MW\Pokemon\Model;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
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
     * @param Json $jsonSerializer
     */
    public function __construct(
        private readonly PokemonCacheProviderInterface $cacheProvider,
        private readonly Client $client,
        private readonly LoggerInterface $logger,
        private readonly Provider $configProvider,
        private readonly ManagerInterface $messageManager,
        private readonly Json $jsonSerializer
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
            try {
                $apiResponseContent = $this->jsonSerializer->unserialize($apiResponseContent);
            } catch (\Exception $e) {
                $this->logger->error('Wrong API response content. ' . $e->getMessage());
                $this->messageManager->addErrorMessage(__('Can\'t get pokemon data.'));

                return $apiResponse;
            }
            if (array_key_exists('name', $apiResponseContent)) {
                $apiResponse['name'] = $apiResponseContent['name'];
            } else {
                $this->messageManager->addErrorMessage(__('Can\'t get pokemon name.'));
            }
            if (array_key_exists('sprites', $apiResponseContent)
                && array_key_exists('front_default', $apiResponseContent['sprites'])) {
                $apiResponse['image'] = $apiResponseContent['sprites']['front_default'];
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
