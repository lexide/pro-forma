<?php

namespace Lexide\ProForma\Test\Composer;

use Composer\Script\Event;
use Composer\Package\PackageInterface;
use Lexide\ProForma\Composer\ComposerHandler;
use Lexide\ProForma\Exception\InstallationException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class ComposerHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected Event|MockInterface $event;
    protected PackageInterface $package;

    public function setUp(): void
    {
        $this->event = \Mockery::mock(Event::class);
        $this->package = \Mockery::mock(PackageInterface::class);
    }

    /**
     * @dataProvider ProFormaExtraProvider
     *
     * @param array $extra
     * @param array $expected
     */
    public function testProFormaExtraConfig(array $extra, array $expected)
    {
        $this->event->shouldReceive("getComposer->getPackage->getExtra")->once()->andReturn($extra);
        $handler = new ComposerHandler($this->event);
        $this->assertSame($expected, $handler->getProFormaExtra());
    }

    /**
     * @dataProvider overwriteProvider
     *
     * @param array $proFormaConfig
     * @param bool $expected
     */
    public function testOverwriteFlag(array $proFormaConfig, bool $expected)
    {
        $this->event->shouldReceive("getComposer->getPackage->getExtra")->once()->andReturn(
            ["lexide/pro-forma" => $proFormaConfig]
        );
        $handler = new ComposerHandler($this->event);
        $this->assertSame($expected, $handler->getOverwriteFlag());
    }

    /**
     * @dataProvider packageConfigProvider
     *
     * @param array $proFormaConfig
     * @param string $packageName
     * @param array $expected
     */
    public function testPackageConfig(array $proFormaConfig, string $packageName, array $expected)
    {
        $this->event->shouldReceive("getComposer->getPackage->getExtra")->once()->andReturn(
            ["lexide/pro-forma" => $proFormaConfig]
        );
        $handler = new ComposerHandler($this->event);
        $this->assertSame($expected, $handler->getProFormaConfigForPackage($packageName));
    }

    /**
     * @dataProvider namespaceProvider
     *
     * @param array $autoload
     * @param string $expectedNamespace
     * @throws InstallationException
     */
    public function testProjectNamespace(array $autoload, string $expectedNamespace)
    {
        $this->event->shouldReceive("getComposer->getPackage->getAutoload")->once()->andReturn($autoload);

        if (empty($expectedNamespace)) {
            $this->expectException(InstallationException::class);
        }

        $handler = new ComposerHandler($this->event);
        $this->assertSame($expectedNamespace, $handler->getProjectNamespace());
    }

    public function testInstalledPackageNames()
    {
        $packageNames = [
            "foo",
            "bar",
            "baz",
            "fiz"
        ];

        $packages = array_fill(0, count($packageNames), $this->package);
        $this->event->shouldReceive("getComposer->getRepositoryManager->getLocalRepository->getPackages")->andReturn($packages);
        $this->package->shouldReceive("getName")->andReturnValues($packageNames);

        $handler = new ComposerHandler($this->event);
        $this->assertSame($packageNames, $handler->getInstalledPackageNames());
    }

    public function ProFormaExtraProvider(): array
    {
        return [
            "empty extra" => [
                [],
                []
            ],
            "no pro forma extra" => [
                ["other" => "stuff"],
                []
            ],
            "pro forma extra" => [
                ["lexide/pro-forma" => ["foo" => "bar"]],
                ["foo" => "bar"]
            ]
        ];
    }

    public function overwriteProvider(): array
    {
        return [
            "no flag" => [
                [],
                false
            ],
            "flag true" => [
                ["overwrite" => true],
                true
            ],
            "flag false" => [
                ["overwrite" => false],
                false
            ]
        ];
    }

    public function packageConfigProvider(): array
    {
        return [
            "no config at all" => [
                [],
                "whatever",
                []
            ],
            "empty config" => [
                ["config" => []],
                "whatever",
                []
            ],
            "config but not for this package" => [
                ["config" => ["foo" => "bar"]],
                "baz",
                []
            ],
            "package config" => [
                ["config" => ["foo" => ["bar" => "baz"]]],
                "foo",
                ["bar" => "baz"]
            ]
        ];
    }

    public function namespaceProvider(): array
    {
        $namespace = "Foo\\Bar";

        return [
            "no autoload" => [
                [],
                ""
            ],
            "psr-4" => [
                [
                    "psr-4" => [
                        $namespace => "blah"
                    ]
                ],
                $namespace
            ],
            "psr-0" => [
                [
                    "psr-0" => [
                        $namespace => "blah"
                    ]
                ],
                $namespace
            ],
            "psr-4 over psr-0" => [
                [
                    "psr-4" => [
                        $namespace => "blah"
                    ],
                    "psr-0" => [
                        "Not\\This\\One" => "blah"
                    ]
                ],
                $namespace
            ]
        ];
    }

}
