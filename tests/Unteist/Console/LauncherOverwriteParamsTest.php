<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Console;

use Delusion\Configurator;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Unteist\Console\Launcher;

/**
 * Class LauncherOverwriteParamsTest
 *
 * @package Tests\Unteist\Console
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class LauncherOverwriteParamsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Launcher
     */
    protected static $launcher;
    /**
     * @var ContainerBuilder
     */
    protected $container;
    /**
     * @var \ReflectionMethod
     */
    protected $method;

    public static function setUpBeforeClass()
    {
        self::$launcher = new Launcher();
    }

    /**
     * @return array
     */
    public function dpParseOptions()
    {
        return [
            [['some string'], ['some string' => '']],
            [['any!chars#in key=value'], ['any!chars#in key' => 'value']],
            [['a=b=value'], ['a' => 'b=value']],
            [['a[b]=c&a[]=d'], ['a' => ['b' => 'c', 'd']]],
            [['a = b = value', ' a [] = c d '], ['a' => ['c d']]],
        ];
    }

    public function testIgnoreEmptyParameters()
    {
        $this->method->invoke(self::$launcher, $this->container, []);
        $this->assertCount(0, $this->container->getParameterBag()->all());
    }

    /**
     * @dataProvider dpParseOptions
     */
    public function testParseOptions(array $arguments, array $expected)
    {
        $this->method->invoke(self::$launcher, $this->container, $arguments);
        $this->assertSame($expected, $this->container->getParameterBag()->all());
    }

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->method = new \ReflectionMethod(self::$launcher, 'overwriteParameters');
        $this->method->setAccessible(true);
    }
}
