<?php

namespace Lexide\ProForma\Test\Template;

use Lexide\ProForma\Template\ProviderConfig\LibraryConfig;
use Lexide\ProForma\Template\ProviderConfig\ProjectConfig;
use Lexide\ProForma\Template\TemplateProviderInterface;

class MockTemplateProvider implements TemplateProviderInterface
{

    protected static array $templatesSequence;
    protected static int $callCount = 0;

    /**
     * @param array $templatesSequence
     */
    public static function setTemplatesSequence(array $templatesSequence): void
    {
        self::$callCount = 0;
        self::$templatesSequence = $templatesSequence;
    }

    /**
     * @return int
     */
    public static function getCallCount(): int
    {
        return self::$callCount;
    }

    /**
     * {@inheritDoc}
     */
    public static function getTemplates(ProjectConfig $projectConfig, LibraryConfig $libraryConfig): array
    {
        ++self::$callCount;
        return array_shift(self::$templatesSequence) ?? [];
    }

}