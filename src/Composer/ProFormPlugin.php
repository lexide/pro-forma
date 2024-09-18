<?php

namespace Lexide\ProForma\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Lexide\ProForma\Exception\InstallationException;
use Lexide\ProForma\Template\ProviderConfig\ProviderConfigFactory;
use Lexide\ProForma\Template\Template;
use Lexide\ProForma\Template\TemplateManager;
use Lexide\ProForma\Template\TemplateProcessor;
use Lexide\ProForma\Template\TemplateProviderInterface;

class ProFormPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // both need to be lower priority than PuzzleDI
            ScriptEvents::POST_INSTALL_CMD => ["install", -10],
            ScriptEvents::POST_UPDATE_CMD => ["install", -10]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        // Nothing to do here
    }

    /**
     * {@inheritDoc}
     */
    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // Nothing to do here
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
        $io->write("<info>lexide/pro-forma</info> WILL NOT <comment>remove auto-generated files. This must be done manually.</comment>");
    }

    /**
     * @param Event $event
     */
    public static function install(Event $event): void
    {

        $handler = new ComposerHandler($event);

        $io = $handler->getIo();

        if (!$handler->hasPackage("lexide/puzzle-di")) {
            $io->write("<info>lexide/pro-forma</info> <comment>requires</comment> <info>lexide/puzzle-di</info> <comment>to operate. Aborting generation.</comment>");
            return;
        }
        try {
            $puzzleClassName = $handler->getProjectNamespace() . "PuzzleConfig";
        } catch (InstallationException) {
            $io->write(
                "<info>lexide/pro-form</info> <comment>could not determine the project namespace. " .
                "Please ensure the project uses PSR-0 or PSR-4 autoloading.</comment>"
            );
            return;
        }

        if (!class_exists($puzzleClassName)) {
            $io->write(
                "<info>lexide/pro-forma</info> <comment>could not find the class</comment> <info$puzzleClassName</info>" .
                "<comment>. Did</comment> <info>lexide/puzzle-di</info> <comment>install correctly?</comment>"
            );
            return;
        }

        $projectPath = $handler->getProjectPath();
        $canOverwrite = $handler->getOverwriteFlag();

        $templateProviders = call_user_func([$puzzleClassName, "getConfigItems"], "lexide/pro-forma");
        if (empty($templateProviders)) {
            $io->write("<info>lexide/pro-forma</info> <comment>did not find any installed (and whitelisted) libraries that provide templates.</comment>");
            return;
        }

        $manager = new TemplateManager(
            $handler,
            new ProviderConfigFactory($handler),
            new TemplateProcessor($io, $projectPath, $canOverwrite)
        );
        $manager->processTemplates($templateProviders);

    }

}