<?php

namespace Lexide\ProForma\Template\ProviderConfig;

class LibraryConfig
{

    protected array $config = [];

    /**
     * @param array $config
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getValue(string $key): mixed
    {
        return $this->config[$key] ?? null;
    }

}