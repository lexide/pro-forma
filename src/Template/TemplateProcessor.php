<?php

namespace Lexide\ProForma\Template;

use Composer\IO\IOInterface;

class TemplateProcessor
{

    protected IOInterface $io;
    protected string $projectPath;
    protected bool $canOverwrite;

    /**
     * @param IOInterface $io
     * @param string $projectPath
     * @param bool $canOverwrite
     */
    public function __construct(IOInterface $io, string $projectPath, bool $canOverwrite)
    {
        $this->io = $io;
        $this->projectPath = $projectPath;
        $this->canOverwrite = $canOverwrite;
    }

    /**
     * @param Template $template
     * @param string $packageInstallPath
     */
    public function process(Template $template, string $packageInstallPath): void
    {
        $outputPath = $this->projectPath . DIRECTORY_SEPARATOR . $template->getOutputPath();
        if (file_exists($outputPath)) {
            if (!$this->canOverwrite) {
                return;
            }
            $this->io->write("<comment>lexide/pro-forma</comment> <info>is overwriting</info> <comment>$outputPath</comment>");
        }

        $templatePath = $packageInstallPath . DIRECTORY_SEPARATOR . $template->getTemplatePath();
        if (!file_exists($templatePath)) {
            $this->io->write("<comment>lexide/pro-forma</comment> <info>could not find the template file</info> <comment>$templatePath</comment>");
            return;
        }

        $templateContent = file_get_contents($templatePath);
        foreach ($template->getReplacements() as $name => $replacement) {
            $templateContent = preg_replace("/{{ *$name *}}/u", $replacement, $templateContent);
        }

        // ensure the output file's directory exists
        if (str_contains($outputPath, DIRECTORY_SEPARATOR)) {
            // remove the filename
            $dirPath = substr($outputPath, 0, strrpos($outputPath, DIRECTORY_SEPARATOR));

            if (!is_dir($dirPath)) {
                if (!mkdir($dirPath, 0664, true)) {
                    $this->io->write("<comment>info/pro-forma</comment> <info>could not create the directory</info> <comment>$dirPath</comment>");
                    return;
                }
            }
        }

        if (file_put_contents($outputPath, $templateContent)) {
            $this->io->write("<comment>lexide/pro-forma</comment> <info>created the file</info> <comment>$outputPath</comment>");
        } else {
            $this->io->write("<comment>lexide/pro-forma</comment> <info>could not create the file</info> <comment>$outputPath</comment>");
        }
    }

}