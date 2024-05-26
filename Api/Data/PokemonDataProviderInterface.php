<?php
declare(strict_types=1);

namespace MW\Pokemon\Api\Data;

interface PokemonDataProviderInterface
{
    /**
     *
     * @param string $pokemonName
     * @return array
     */
    public function getPokemonData(string $pokemonName): array;
}
