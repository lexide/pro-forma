<?php

namespace Lexide\ProForma\Template;

class TemplateFactory
{

    /**
     * @param string $name
     * @param string $templatePath
     * @param string $outputPath
     * @param array $replacements
     * @return Template
     */
    public static function create(string $name, string $templatePath, string $outputPath, array $replacements = []): Template
    {
        $template = new Template();
        $template->setName($name);
        $template->setTemplatePath($templatePath);
        $template->setOutputPath($outputPath);
        $template->setReplacements($replacements);
        return $template;
    }

}