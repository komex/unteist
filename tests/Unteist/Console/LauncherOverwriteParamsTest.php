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

    public function setUp()
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

    public function tearDown()
    {
        $this->params->clear();
    }

    /**
     * @return array
     */
    public function dpParseOptionsWithReplaceChars()
    {
        return [
            ['some string'],
            ['some.string'],
            ['some_string'],
            ['some+string'],
        ];
    }

    /**
     * @param string $actual
     *
     * @dataProvider dpParseOptionsWithReplaceChars
     */
    public function testParseOptionsWithReplaceChars($actual)
    {
        /** @var \Delusion\Suggestible $input */
        $input = new ArgvInput();
        Configurator::setCustomBehavior($input, 'getOption', [$actual]);
        $this->method->invoke(self::$launcher, $input);
        $this->assertEquals(['some.string' => ''], $this->params->all());
    }

    public function testParseOptions()
    {
        $this->params->add(['a' => ['ok']]);
        /** @var \Delusion\Suggestible $input */
        $input = new ArgvInput();
        Configurator::setCustomBehavior($input, 'getOption', ['test=ok1', 'test=ok2', 'a[]=ok1', 'a[]=ok2']);
        $this->method->invoke(self::$launcher, $input);
        $this->assertEquals(['test' => 'ok2', 'a' => ['ok1', 'ok2']], $this->params->all());
    }
}
