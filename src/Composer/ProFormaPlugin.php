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

class ProFormaPlugin implements PluginInterface, EventSubscriberInterface
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
        $io->write("<comment>lexide/pro-forma</comment> WILL NOT <info>remove auto-generated files. This must be done manually.</info>");
    }

    /**
     * @param Event $event
     */
    public static function install(Event $event): void
    {

        $handler = new ComposerHandler($event);

        $io = $handler->getIo();

        if (!$handler->hasPackage("lexide/puzzle-di")) {
            $io->write("<comment>lexide/pro-forma</comment> <info>requires</info> <comment>lexide/puzzle-di</comment> <info>to operate. Aborting generation.</info>");
            return;
        }
        try {
            $puzzleClassName = $handler->getProjectNamespace() . "PuzzleConfig";
        } catch (InstallationException) {
            $io->write(
                "<comment>lexide/pro-form</comment> <info>could not determine the project namespace. " .
                "Please ensure the project uses PSR-0 or PSR-4 autoloading.</info>"
            );
            return;
        }

        if (!class_exists($puzzleClassName)) {
            $io->write(
                "<comment>lexide/pro-forma</comment> <info>could not find the class</info> <comment>$puzzleClassName</comment>" .
                "<info>. Did</info> <comment>lexide/puzzle-di</comment> <info>install correctly?</info>"
            );
            return;
        }

        $projectPath = $handler->getProjectPath();
        $canOverwrite = $handler->getOverwriteFlag();

        $templateProviders = call_user_func([$puzzleClassName, "getConfigItems"], "lexide/pro-forma");
        if (empty($templateProviders)) {
            $io->write("<comment>lexide/pro-forma</comment> <info>did not find any installed (and whitelisted) libraries that provide templates.</info>");
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