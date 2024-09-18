<?php

namespace Lexide\ProForma\Test\Template;

use Composer\IO\IOInterface;
use Lexide\ProForma\Composer\ComposerHandler;
use Lexide\ProForma\Template\ProviderConfig\LibraryConfig;
use Lexide\ProForma\Template\ProviderConfig\ProjectConfig;
use Lexide\ProForma\Template\ProviderConfig\ProviderConfigFactory;
use Lexide\ProForma\Template\Template;
use Lexide\ProForma\Template\TemplateManager;
use Lexide\ProForma\Template\TemplateProcessor;
use Lexide\ProForma\Template\TemplateProviderInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class TemplateManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected ComposerHandler|MockInterface $handler;
    protected ProviderConfigFactory|MockInterface $configFactory;
    protected TemplateProcessor|MockInterface $processor;
    protected ProjectConfig|MockInterface $projectConfig;
    protected LibraryConfig|MockInterface $libraryConfig;
    protected Template|MockInterface $template;
    protected IOInterface|MockInterface $io;

    public function setUp(): void
    {
        $this->handler = \Mockery::mock(ComposerHandler::class);
        $this->configFactory = \Mockery::mock(ProviderConfigFactory::class);
        $this->processor = \Mockery::mock(TemplateProcessor::class);
        $this->projectConfig = \Mockery::mock(ProjectConfig::class);
        $this->libraryConfig = \Mockery::mock(LibraryConfig::class);
        $this->template = \Mockery::mock(Template::class);
        $this->io = \Mockery::mock(IOInterface::class);
        $this->configFactory->shouldReceive("createProjectConfig")->andReturn($this->projectConfig);
        $this->configFactory->shouldReceive("createLibraryConfig")->andReturn($this->libraryConfig);
        $this->handler->shouldReceive("getIo")->andReturn($this->io);
        $this->handler->shouldReceive("getInstallPathByName")->andReturnArg(0);
    }

    /**
     * @dataProvider templateConfigProvider
     *
     * @param array $templateConfig
     */
    public function testTemplateProcessing(array $templateConfig)
    {
        $templateProviders = [];
        $templateSequence = [];
        foreach ($templateConfig as $package => $templateCount) {
            $templateProviders[$package] = MockTemplateProvider::class;
            $templateSequence[] = array_fill(0, $templateCount, $this->template);
            $this->processor->shouldReceive("process")->with($this->template, $package)->times($templateCount);
        }
        MockTemplateProvider::setTemplatesSequence($templateSequence);

        $manager = new TemplateManager($this->handler, $this->configFactory, $this->processor);
        $manager->processTemplates($templateProviders);

        $this->assertSame(count($templateConfig), MockTemplateProvider::getCallCount());
    }

    /**
     * @dataProvider providerClassErrorProvider
     *
     * @param string $providerClass
     * @param string $expectedMessageRegex
     */
    public function testProviderClassErrors(string $providerClass, string $expectedMessageRegex)
    {
        $templateProviders = [
            "foo" => $providerClass
        ];

        $this->io->shouldReceive("write")->with(\Mockery::pattern($expectedMessageRegex))->once();
        $this->processor->shouldNotReceive("process");

        $manager = new TemplateManager($this->handler, $this->configFactory, $this->processor);
        $manager->processTemplates($templateProviders);
    }

    public function testInvalidTemplateError()
    {
        $templateProviders = [
            "foo" => MockTemplateProvider::class
        ];

        MockTemplateProvider::setTemplatesSequence([[$this->handler]]);

        $this->io->shouldReceive("write")->with(\Mockery::pattern("/.*invalid template configuration.*/"))->once();
        $this->processor->shouldNotReceive("process");

        $manager = new TemplateManager($this->handler, $this->configFactory, $this->processor);
        $manager->processTemplates($templateProviders);

        $this->assertSame(1, MockTemplateProvider::getCallCount());

    }

    public function testErrorsDontAffectValidProviders()
    {
        $oneCount = 3;
        $twoCount = 5;

        $templateProviders = [
            "foo" => "IDontExist",
            "bar" => TemplateManager::class,
            "one" => MockTemplateProvider::class,
            "baz" => MockTemplateProvider::class,
            "two" => MockTemplateProvider::class
        ];
        $templateSequence = [
            array_fill(0, $oneCount, $this->template),
            [$this->handler],
            array_fill(0, $twoCount, $this->template)
        ];

        $this->processor->shouldReceive("process")->with($this->template, "one")->times($oneCount);
        $this->processor->shouldReceive("process")->with($this->template, "two")->times($twoCount);

        MockTemplateProvider::setTemplatesSequence($templateSequence);

        $this->io->shouldIgnoreMissing();

        $manager = new TemplateManager($this->handler, $this->configFactory, $this->processor);
        $manager->processTemplates($templateProviders);

        $this->assertSame(count($templateSequence), MockTemplateProvider::getCallCount());
    }

    public function templateConfigProvider(): array
    {
        return [
            "No templates" => [
                []
            ],
            "Single template" => [
                [
                    "foo" => 1
                ]
            ],
            "One provider, multiple templates" => [
                [
                    "foo" => 5
                ]
            ],
            "One provider, no templates" => [
                [
                    "foo" => 0
                ]
            ],
            "Multiple providers" => [
                [
                    "foo" => 1,
                    "bar" => 1
                ]
            ],
            "Multiple providers, multiple templates" => [
                [
                    "foo" => 3,
                    "bar" => 5,
                    "baz" => 2
                ]
            ]
        ];
    }

    public function providerClassErrorProvider(): array
    {
        return [
            "class not exists" => [
                "IDontExist",
                "/.*could not find.*IDontExist.*/"
            ],
            "class not a template provider" => [
                TemplateManager::class,
                "/.*does not implement.*" . preg_quote(TemplateProviderInterface::class) . ".*/"
            ],
        ];
    }

}
