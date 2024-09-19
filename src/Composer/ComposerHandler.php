<?php

namespace Lexide\ProForma\Composer;

use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Script\Event;
use Composer\Util\Platform;
use Lexide\ProForma\Exception\InstallationException;

class ComposerHandler
{
    protected Event $event;

    /**
     * @param Event $event
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    /**
     * @return IOInterface
     */
    public function getIo(): IOInterface
    {
        return $this->event->getIO();
    }

    /**
     * @return string
     */
    public function getProjectName(): string
    {
        return $this->event->getComposer()->getPackage()->getName();
    }

    /**
     * @return array
     */
    public function getProFormaExtra(): array
    {
        return $this->event->getComposer()->getPackage()->getExtra()["lexide/pro-forma"] ?? [];
    }

    /**
     * @return bool
     */
    public function getOverwriteFlag(): bool
    {
        return $this->getProFormaExtra()["overwrite"] ?? false;
    }

    /**
     * @param string $packageName
     * @return array
     */
    public function getProFormaConfigForPackage(string $packageName): array
    {
        return $this->getProFormaExtra()["config"][$packageName] ?? [];
    }

    /**
     * @return string
     */
    public function getProjectPath(): string
    {
        return Platform::getCwd();
    }

    /**
     * @return string
     * @throws InstallationException
     */
    public function getProjectNamespace(): string
    {
        $autoload = $this->event->getComposer()->getPackage()->getAutoload();
        $entries = ($autoload["psr-4"] ?? []) ?: $autoload["psr-0"] ?? [];
        if (empty($entries)) {
            throw new InstallationException("Project namespace could not be identified. ProForma requires PSR-0 or PSR-4 autoloading");
        }
        return array_keys($entries)[0];
    }

    /**
     * @return array
     */
    public function getInstalledPackageNames(): array
    {
        return array_map(
            fn(PackageInterface $package) => $package->getName(),
            $this->event->getComposer()->getRepositoryManager()->getLocalRepository()->getPackages()
        );
    }

    /**
     * @param string $packageName
     * @return bool
     */
    public function hasPackage(string $packageName): bool
    {
        return $this->getPackage($packageName) instanceof PackageInterface;
    }

    /**
     * @param string $packageName
     * @return ?PackageInterface
     */
    protected function getPackage(string $packageName): ?PackageInterface
    {
        return $this->event->getComposer()->getRepositoryManager()->findPackage($packageName, "*");
    }

    /**
     * @param string $packageName
     * @return ?string
     */
    public function getInstallPathByName(string $packageName): ?string
    {
        return $this->event->getComposer()->getInstallationManager()->getInstallPath(
            $this->getPackage($packageName)
        );
    }

}