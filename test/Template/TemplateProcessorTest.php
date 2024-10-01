<?php

namespace Lexide\ProForma\Test\Template;

use Composer\IO\IOInterface;
use Lexide\ProForma\Template\Template;
use Lexide\ProForma\Template\TemplateProcessor;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\TestCase;

class TemplateProcessorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected IOInterface|MockInterface $io;
    protected Template|MockInterface $template;
    protected vfsStreamDirectory $testDir;

    public function setUp(): void
    {

        $this->io = \Mockery::mock(IOInterface::class);
        $this->io->shouldIgnoreMissing();

        $this->template = \Mockery::mock(Template::class);
        $this->template->shouldReceive("getName")->andReturn("blah");

        vfsStreamWrapper::register();
        $this->testDir = new vfsStreamDirectory("test", 0777);
        vfsStreamWrapper::setRoot($this->testDir);
    }

    /**
     * @dataProvider overwriteProvider
     *
     * @param bool $overwrite
     * @param bool $fileExists
     */
    public function testOverwriting(bool $overwrite, bool $fileExists)
    {
        $path = vfsStreamWrapper::getRoot()->url();
        $fileName = "exists";
        $templateFileName = "template";
        $outputContent = "foo";

        $templateFile = vfsStream::newFile($templateFileName, 0777);
        $templateFile->write($outputContent);
        $this->testDir->addChild($templateFile);

        if ($fileExists) {
            $this->testDir->addChild(vfsStream::newFile($fileName, 0777));
        }

        $this->template->shouldReceive("getOutputPath")->once()->andReturn($fileName);
        $this->template->shouldReceive("getTemplatePath")->andReturn($templateFileName);
        $this->template->shouldReceive("getReplacements")->andReturn([]);

        if ($fileExists && $overwrite) {
            $this->io->shouldReceive("write")->with(\Mockery::pattern("/overwriting/"))->once();
        }

        $processor = new TemplateProcessor($this->io, $path, $overwrite);
        $processor->process($this->template, $path);

        $filePath = "$path/$fileName";
        $this->assertFileExists($filePath);
        $contents = file_get_contents($filePath);

        if ($fileExists && !$overwrite) {
            $this->assertNotSame($outputContent, $contents);
        }  else {
            $this->assertSame($outputContent, $contents);
        }

    }

    /**
     * @dataProvider templateProvider
     *
     * @param string $templateContent
     * @param array $replacements
     * @param string $expectedOutputContent
     */
    public function testCreateTemplate(string $templateContent, array $replacements, string $expectedOutputContent)
    {
        $path = vfsStreamWrapper::getRoot()->url();
        $fileName = "exists";
        $templateFileName = "template";

        $templateFile = vfsStream::newFile($templateFileName, 0777);
        $templateFile->write($templateContent);
        $this->testDir->addChild($templateFile);

        $this->template->shouldReceive("getOutputPath")->once()->andReturn($fileName);
        $this->template->shouldReceive("getTemplatePath")->andReturn($templateFileName);
        $this->template->shouldReceive("getReplacements")->andReturn($replacements);


        $processor = new TemplateProcessor($this->io, $path, false);
        $processor->process($this->template, $path);

        $filePath = "$path/$fileName";
        $this->assertFileExists($filePath);
        $contents = file_get_contents($filePath);
        $this->assertSame($expectedOutputContent, $contents);
    }

    public function testCreatingDirectories()
    {
        $path = vfsStreamWrapper::getRoot()->url();
        $templateFileName = "blah";
        // output file in subdirectories
        $outputDirectory = "foo/bar";
        $outputFile = "$outputDirectory/baz";

        $templateFile = vfsStream::newFile($templateFileName, 0777);
        $this->testDir->addChild($templateFile);

        $processor = new TemplateProcessor($this->io, $path, false);

        $this->template->shouldReceive("getTemplatePath")->andReturn($templateFileName);
        $this->template->shouldReceive("getOutputPath")->once()->andReturn($outputFile);
        $this->template->shouldReceive("getReplacements")->andReturn([]);

        $directoryPath = "$path/$outputDirectory";

        $this->assertDirectoryDoesNotExist($directoryPath);

        $processor->process($this->template, $path);

        $this->assertDirectoryExists($directoryPath);
        $this->assertFileExists("$path/$outputFile");
    }

    public function testMissingTemplate()
    {
        $path = vfsStreamWrapper::getRoot()->url();
        $outputFile = "bar";

        $processor = new TemplateProcessor($this->io, $path, false);

        $this->template->shouldReceive("getTemplatePath")->once()->andReturn("foo");
        $this->template->shouldReceive("getOutputPath")->once()->andReturn($outputFile);

        $this->io->shouldReceive("write")->with(\Mockery::pattern("/could not find/"))->once();

        $processor->process($this->template, $path);

        $this->assertFileDoesNotExist("$path/$outputFile");
    }

    public function templateProvider(): array
    {
        return [
            "file copy" => [
                "foo",
                [],
                "foo"
            ],
            "single replacement" => [
                "Hello {{foo}}",
                ["foo" => "World"],
                "Hello World"
            ],
            "multiple replacements" => [
                "Hello, my {{foo}} is {{bar}}. I am a {{baz}}",
                ["foo" => "name", "bar" => "Bob", "baz" => "template"],
                "Hello, my name is Bob. I am a template"
            ],
            "complex replacements" => [
                "{{foo}} {{bar}}{{baz}}",
                [
                    "foo" => "{{greeting}}",
                    "bar" => "{{na",
                    "baz" => "me}}",
                    "greeting" => "Hello",
                    "name" => "Bob"
                ],
                "Hello Bob"
            ],
            "missing replacement" => [
                "What is {{this}}",
                [],
                "What is {{this}}"
            ],
            "leading space" => [
                "{{ foo}}",
                ["foo" => "bar"],
                "bar"
            ],
            "trailing space" => [
                "{{foo }}",
                ["foo" => "bar"],
                "bar"
            ],
            "surrounding spaces" => [
                "{{ foo }}",
                ["foo" => "bar"],
                "bar"
            ],
            "lots of spaces" => [
                "{{       foo                }}",
                ["foo" => "bar"],
                "bar"
            ]
        ];
    }

    public function overwriteProvider(): array
    {
        return [
            "not overwriting, file exists" => [false, true],
            "overwriting, file exists" => [true, true],
            "overwriting, no file" => [true, false]
        ];
    }

}
