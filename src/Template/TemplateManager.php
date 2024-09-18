<?php

namespace Lexide\ProForma\Template;

use Lexide\ProForma\Composer\ComposerHandler;
use Lexide\ProForma\Exception\InstallationException;
use Lexide\ProForma\Template\ProviderConfig\ProviderConfigFactory;

class TemplateManager
{

    protected ComposerHandler $handler;
    protected ProviderConfigFactory $configFactory;
    protected TemplateProcessor $processor;

    /**
     * @param ComposerHandler $handler
     * @param ProviderConfigFactory $configFactory
     * @param TemplateProcessor $processor
     */
    public function __construct(ComposerHandler $handler, ProviderConfigFactory $configFactory, TemplateProcessor $processor)
    {
        $this->handler = $handler;
        $this->configFactory = $configFactory;
        $this->processor = $processor;
    }

    /**
     * @param array $templateProviderClasses
     */
    public function processTemplates(array $templateProviderClasses): void
    {
        $io = $this->handler->getIo();

        try {
            $projectConfig = $this->configFactory->createProjectConfig();
        } catch (InstallationException) {
            $io->write("<info>lexide/pro-forma</info> <comment>could not determine the project namespace.</comment>");
            return;
        }

        foreach ($templateProviderClasses as $packageName => $templateProviderClass) {
            if (!class_exists($templateProviderClass)) {
                $io->write("<info>lexide/pro-forma</info> <comment>could not find the template provider class </comment><info>$templateProviderClass.</info>");
                continue;
            }
            if (!is_subclass_of($templateProviderClass, TemplateProviderInterface::class)) {
                $io->write(
                    "<info>lexide/pro-forma</info> <comment>could not use the template provider </comment><info>$templateProviderClass</info> " .
                    "<comment>as it does not implement </comment><info>" . TemplateProviderInterface::class . ".</info>"
                );
                continue;
            }

            $libraryConfig = $this->configFactory->createLibraryConfig($packageName);
            $templates = call_user_func([$templateProviderClass, "getTemplates"], $projectConfig, $libraryConfig);

            $packageInstallPath = $this->handler->getInstallPathByName($packageName);

            foreach ($templates as $template) {
                if (!$template instanceof Template) {
                    $io->write(
                        "<info>lexide/pro-forma</info> <comment>found invalid template configuration " .
                        "from the provider </comment><info>$templateProviderClass.</info>"
                    );
                    continue 2;
                }

                $this->processor->process($template, $packageInstallPath);

            }

        }
    }
}