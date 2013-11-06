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
     * @var ParameterBagInterface
     */
    protected $params;
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

    /**
     * @dataProvider dpParseOptions
     */
    public function testParseOptions(array $arguments, array $expected)
    {
        /** @var \Delusion\Suggestible $input */
        $input = new ArgvInput();
        Configurator::setCustomBehavior($input, 'getOption', $arguments);
        $this->method->invoke(self::$launcher, $input);
        $this->assertSame($expected, $this->params->all());
    }

    protected function setUp()
    {
        $this->method = new \ReflectionMethod(self::$launcher, 'overwriteParams');
        $this->method->setAccessible(true);
        $property = new \ReflectionProperty(self::$launcher, 'container');
        $property->setAccessible(true);
        /** @var ContainerBuilder $container */
        $container = $property->getValue(self::$launcher);
        $this->params = $container->getParameterBag();

        $this->assertInternalType('array', $this->params->all());
        $this->assertEmpty($this->params->all());
    }

    protected function tearDown()
    {
        $this->params->clear();
    }
}
