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
            $io->write("<comment>lexide/pro-forma</comment> <info>could not determine the project namespace.</info>");
            return;
        }

        foreach ($templateProviderClasses as $packageName => $templateProviderClass) {
            if (!class_exists($templateProviderClass)) {
                $io->write("<comment>lexide/pro-forma</comment> <info>could not find the template provider class </info><comment>$templateProviderClass.</comment>");
                continue;
            }
            if (!is_subclass_of($templateProviderClass, TemplateProviderInterface::class)) {
                $io->write(
                    "<comment>lexide/pro-forma</comment> <info>could not use the template provider </info><comment>$templateProviderClass</comment> " .
                    "<info>as it does not implement </info><comment>" . TemplateProviderInterface::class . ".</comment>"
                );
                continue;
            }

            $libraryConfig = $this->configFactory->createLibraryConfig($packageName);
            $templates = call_user_func([$templateProviderClass, "getTemplates"], $projectConfig, $libraryConfig);
            $messages = call_user_func([$templateProviderClass, "getMessages"], $projectConfig, $libraryConfig);

            if (!empty($messages)) {
                $io->write("<comment>lexide/pro-forma</comment> <info>was passed messages from the TemplateProvider for</info> <comment>$packageName</comment>");
                foreach ($messages as $message) {
                    if (is_string($message)) {
                        $io->write("</info>* $message");
                    }
                }
            }

            $packageInstallPath = $this->handler->getInstallPathByName($packageName);

            foreach ($templates as $template) {
                if (!$template instanceof Template) {
                    $io->write(
                        "<comment>lexide/pro-forma</comment> <info>found invalid template configuration " .
                        "from the provider </info><comment>$templateProviderClass.</comment>"
                    );
                    continue 2;
                }

                $this->processor->process($template, $packageInstallPath);

            }

        }
    }
}