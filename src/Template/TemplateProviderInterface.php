<?php

namespace Lexide\ProForma\Template;

use Lexide\ProForma\Template\ProviderConfig\LibraryConfig;
use Lexide\ProForma\Template\ProviderConfig\ProjectConfig;

interface TemplateProviderInterface
{

    /**
     * @param ProjectConfig $projectConfig
     * @param LibraryConfig $libraryConfig
     * @return array
     */
    public static function getTemplates(ProjectConfig $projectConfig, LibraryConfig $libraryConfig): array;

    /**
     * @return array
     */
    public static function getMessages(): array;

}