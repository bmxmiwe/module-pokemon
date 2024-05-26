<?php
declare(strict_types=1);

namespace MW\Pokemon\Api;

interface PokemonCacheProviderInterface
{
    /**
     *
     * @param string $pokemonName
     * @param array $response
     * @return void
     */
    public function saveResponse(string $pokemonName, array $response): void;

    /**
     *
     * @param string $pokemonName
     * @return array|null
     */
    public function loadResponse(string $pokemonName): ?array;
}
