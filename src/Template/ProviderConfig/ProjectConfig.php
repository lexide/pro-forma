<?php

namespace Lexide\ProForma\Template\ProviderConfig;

class ProjectConfig
{

    protected string $namespace = "";
    protected array $installedPackages = [];

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     */
    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    /**
     * @return array
     */
    public function getInstalledPackages(): array
    {
        return $this->installedPackages;
    }

    /**
     * @param string $packageName
     * @return bool
     */
    public function hasInstalledPackage(string $packageName): bool
    {
        return isset(array_flip($this->installedPackages)[$packageName]);
    }

    /**
     * @param array $installedPackages
     */
    public function setInstalledPackages(array $installedPackages): void
    {
        $this->installedPackages = $installedPackages;
    }


}