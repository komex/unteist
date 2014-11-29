<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Processor;

use Influence\RemoteControlUtils as RC;
use Influence\ReturnStrategy\Value;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Unteist\Processor\Controller\Controller;
use Unteist\Processor\Controller\ControllerParentInterface;
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
        $container = new ContainerBuilder();
        $controller = new Controller($container);
        $container->set('logger', new Logger('log'));
        $container->set('controller', $controller);

        $manifest = RC::getObject($controller);
        $manifest->getMethod('setRunner')->setLog(true);
        $manifest->getMethod('switchTo')->setLog(true);
        $manifest->getMethod('switchTo')->setValue(new Value(null));

        $runner = new Runner($container);
        $runnerLogs = $manifest->getMethod('setRunner')->getLogs();
        $switchLogs = $manifest->getMethod('switchTo')->getLogs();
        $this->assertCount(1, $runnerLogs);
        $this->assertSame([$runner], $runnerLogs[0]);
        $this->assertCount(1, $switchLogs);
        $this->assertSame([ControllerParentInterface::CONTROLLER_RUN], $switchLogs[0]);

        RC::removeObject($controller);
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
