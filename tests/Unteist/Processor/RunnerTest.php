<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Processor;

use Delusion\Configurator;
use Delusion\Suggestible;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Unteist\Processor\Controller\Controller;
use Unteist\Processor\Runner;

/**
 * Class RunnerTest
 *
 * @package Tests\Unteist\Processor
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class RunnerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Runner
     */
    protected $runner;
    /**
     * @var Suggestible
     */
    protected $controller;
    /**
     * @var Suggestible
     */
    protected $logger;

    /**
     * Prepare tests.
     */
    public function setUp()
    {
        $container = new ContainerBuilder;
        /** @var Suggestible $controller */
        $controller = new Controller($container);
        Configurator::setCustomBehavior($controller, 'setRunner', null);
        Configurator::setCustomBehavior($controller, 'switchTo', null);
        Configurator::storeInvokes($controller, true);
        $container->set('controller', $controller);
        /** @var Suggestible $logger */
        $logger = new Logger('test');
        Configurator::setCustomBehavior($logger, 'debug', null);
        Configurator::storeInvokes($logger, true);
        $container->set('logger', $logger);
        $this->runner = new Runner($container);
        $this->controller = $controller;
        $this->logger = $logger;
    }

    /**
     * Test constructor behavior.
     */
    public function testConstructor()
    {
        $this->assertCount(2, Configurator::getAllInvokes($this->controller));

        $invokes = Configurator::getInvokes($this->controller, 'setRunner');
        $this->assertCount(1, $invokes);
        $this->assertSame([$this->runner], $invokes[0]);

        $invokes = Configurator::getInvokes($this->controller, 'switchTo');
        $this->assertCount(1, $invokes);
        $this->assertSame(['controller.run'], $invokes[0]);
    }

    /**
     * @return array
     */
    public function dpGetAnnotations()
    {
        return [
            ['', []],
            [['a' => 'b'], []],
            [$this->generateDocBlock(['* just a string' . "\n", 'another string' . "\r\n"]), []],
            [$this->generateDocBlock(['just a string' . "\n", '*@test' . "\n"]), ['test' => null]],
            [$this->generateDocBlock(['* @depends method1, method2 ' . "\r"]), ['depends' => 'method1, method2']],
            [
                $this->generateDocBlock(
                    [' *@depends method1, method2 ' . "\r\n", 'str' . "\n", '* @dataProvider * ' . "\n", 'line' . "\n"]
                ),
                ['depends' => 'method1, method2', 'dataProvider' => '*']
            ],
            [$this->generateDocBlock(['* @depends method @test' . "\n\r"]), ['depends' => 'method @test']],
            [
                $this->generateDocBlock(['* @depends method' . "\n", '* @depends method1' . "\r", '* @api' . "\n"]),
                ['depends' => 'method1', 'api' => null]
            ],
        ];
    }

    /**
     * Test parsing annotations.
     *
     * @param string $comments
     * @param array $expected
     *
     * @dataProvider dpGetAnnotations
     */
    public function testGetAnnotations($comments, array $expected)
    {
        $this->assertSame($expected, Runner::getAnnotations($comments), 'Error while parsing annotations.');
    }

    /**
     * Test finter invalid event listeners.
     */
    public function testRegisterInvalidEventListener()
    {
        $method = new \ReflectionMethod($this->runner, 'registerEventListener');
        $method->setAccessible(true);
        $method->invoke($this->runner, 'invalid.event', 'precondition');

        $this->assertCount(0, $this->runner->getPrecondition()->getListeners());
        $this->assertCount(0, Configurator::getAllInvokes($this->logger));
    }

    /**
     * Generate doc block comment using specified lines.
     *
     * @param array $lines All lines in array must have "End Of Line" symbol.
     *
     * @return string
     */
    private function generateDocBlock(array $lines)
    {
        return '/**' . PHP_EOL . join('', $lines) . PHP_EOL . '*/';
    }
}
