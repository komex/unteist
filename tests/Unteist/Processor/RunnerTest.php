<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Processor;

use Influence\RemoteControl;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Unteist\Event\EventStorage;
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
     * @var Controller
     */
    protected $controller;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Test constructor behavior.
     */
    public function testConstructor()
    {
        $manifest = RemoteControl::control($this->controller);
        $this->assertCount(2, $manifest);

        $invokes = $manifest->getCalls('setRunner');
        $this->assertCount(1, $invokes);
        $this->assertSame([$this->runner], $invokes[0]);

        $invokes = $manifest->getCalls('switchTo');
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
        $this->assertCount(0, RemoteControl::control($this->logger));
    }

    /**
     * @return array
     */
    public function dpRegisterEventListener()
    {
        return [
            ['beforeTest', EventStorage::EV_BEFORE_TEST],
            ['afterTest', EventStorage::EV_AFTER_TEST],
            ['beforeCase', EventStorage::EV_BEFORE_CASE],
            ['afterCase', EventStorage::EV_AFTER_CASE],
        ];
    }

    /**
     * Test filter invalid event listeners.
     *
     * @param $annotation
     * @param $event
     *
     * @dataProvider dpRegisterEventListener
     */
    public function testRegisterEventListener($annotation, $event)
    {
        $method = new \ReflectionMethod($this->runner, 'registerEventListener');
        $method->setAccessible(true);
        $method->invoke($this->runner, $annotation, 'precondition');

        $listeners = $this->runner->getPrecondition()->getListeners();
        $this->assertCount(1, RemoteControl::control($this->logger));
        $this->assertCount(1, $listeners);
        $this->assertArrayHasKey($event, $listeners);
    }

    /**
     * Prepare tests.
     */
    protected function setUp()
    {
        $container = new ContainerBuilder;
        $controller = new Controller($container);
        $manifest = RemoteControl::control($controller);
        $manifest->setReturn('setRunner', null);
        $manifest->setReturn('switchTo', null);
        $manifest->registerCalls(true);
        $container->set('controller', $controller);
        $logger = new Logger('test');
        $manifest = RemoteControl::control($logger);
        $manifest->setReturn('debug', null);
        $manifest->registerCalls(true);
        $container->set('logger', $logger);
        $this->runner = new Runner($container);
        $this->controller = $controller;
        $this->logger = $logger;
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
