<?php

namespace Lexide\ProForma\Template;

class Template
{

    protected string $name = "";
    protected string $templatePath = "";
    protected string $outputPath = "";
    protected array $replacements = [];

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getTemplatePath(): string
    {
        return $this->templatePath;
    }

    /**
     * @param string $templatePath
     */
    public function setTemplatePath(string $templatePath): void
    {
        $this->templatePath = $templatePath;
    }

    /**
     * @return string
     */
    public function getOutputPath(): string
    {
        return $this->outputPath;
    }

    /**
     * @param string $outputPath
     */
    public function setOutputPath(string $outputPath): void
    {
        $this->outputPath = $outputPath;
    }

    /**
     * @return array
     */
    public function getReplacements(): array
    {
        return $this->replacements;
    }

    /**
     * @param array $replacements
     */
    public function setReplacements(array $replacements): void
    {
        $this->replacements = $replacements;
    }

}