<?php

namespace Lexide\ProForma\Template\ProviderConfig;

use Lexide\ProForma\Composer\ComposerHandler;
use Lexide\ProForma\Exception\InstallationException;

class ProviderConfigFactory
{

    protected ComposerHandler $handler;

    /**
     * @param ComposerHandler $handler
     */
    public function __construct(ComposerHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @return ProjectConfig
     * @throws InstallationException
     */
    public function createProjectConfig(): ProjectConfig
    {
        $config = new ProjectConfig();
        $config->setNamespace($this->handler->getProjectNamespace());
        $config->setInstalledPackages($this->handler->getInstalledPackageNames());
        return $config;
    }

    /**
     * @param string $libraryName
     * @return LibraryConfig
     */
    public function createLibraryConfig(string $libraryName): LibraryConfig
    {
        $config = new LibraryConfig();
        $config->setConfig($this->handler->getProFormaConfigForPackage($libraryName));
        return $config;
    }

}