<?php
declare(strict_types=1);

namespace MW\Pokemon\Api\Config;

interface ProviderInterface
{
    /**
     *
     * @return bool
     */
    public function pokemonModuleIsEnabled(): bool;

    /**
     *
     * @return string
     */
    public function getApiUrl(): string;
}
