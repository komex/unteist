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
use Symfony\Component\EventDispatcher\EventDispatcher;
use Unteist\Event\EventStorage;
use Unteist\Processor\Processor;

/**
 * Class ProcessorTest
 *
 * @package Tests\Unteist\Processor
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class ProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test constructor behavior.
     */
    public function testConstructor()
    {
        $container = new ContainerBuilder();
        $manifest = RemoteControl::control($container);
        $manifest->registerCalls(true);
        $self = $this;
        $manifest->setReturn(
            'get',
            function ($id) use ($self) {
                $self->assertSame('logger', $id);
            }
        );
        new Processor($container);
        $this->assertCount(1, $manifest);
    }

    /**
     * Test backup and restore $GLOBALS.
     */
    public function testBackUpGlobals()
    {
        $this->assertArrayNotHasKey('Unteist', $GLOBALS);
        $GLOBALS['Unteist'] = 'ok';
        $container = new ContainerBuilder();
        RemoteControl::control($container)->setReturn('get', null);
        $processor = new Processor($container);
        $method = new \ReflectionMethod($processor, 'backupGlobals');
        $method->setAccessible(true);
        $method->invoke($processor);
        $this->assertSame('ok', $GLOBALS['Unteist']);
        unset($GLOBALS['Unteist']);
        $this->assertArrayNotHasKey('Unteist', $GLOBALS);
        $method = new \ReflectionMethod($processor, 'restoreGlobals');
        $method->setAccessible(true);
        $method->invoke($processor);
        $this->assertSame('ok', $GLOBALS['Unteist']);
    }

    /**
     * @return array
     */
    public function dpRun()
    {
        return [
            [new \ArrayObject(), 1, 0],
            [new \ArrayObject([new \SplFileInfo('a')]), 1, 1],
            [new \ArrayObject([new \SplFileInfo('a'), new \SplFileInfo('b')]), 0, 0],
        ];
    }

    /**
     * Test run suites behavior.
     *
     * @param \ArrayObject $suites
     * @param int $executor
     * @param int $statusCode
     *
     * @dataProvider dpRun
     */
    public function testRun(\ArrayObject $suites, $executor, $statusCode)
    {
        $container = new ContainerBuilder();
        $container->setParameter('suites', $suites);
        $logger = new Logger('test');
        RemoteControl::control($logger)->setReturn('info', null);
        $container->set('logger', $logger);
        $dispatcher = new EventDispatcher();
        $dispatcherManifest = RemoteControl::control($dispatcher);
        $dispatcherManifest->registerCalls(true);
        $container->set('dispatcher', $dispatcher);
        $processor = new Processor($container);
        $processorManifest = RemoteControl::control($processor);
        $processorManifest->registerCalls(true);
        $processorManifest->setReturn('executor', $executor);
        $this->assertSame($statusCode, $processor->run());

        $this->assertSame(
            [['dispatch', [EventStorage::EV_APP_STARTED]], ['dispatch', [EventStorage::EV_APP_FINISHED]]],
            $dispatcherManifest->getAllCalls(),
            'Dispatcher did not send application events.'
        );

        $this->assertSame(1, $processorManifest->getCallsCount('run'));
        $this->assertSame($suites->count(), $processorManifest->getCallsCount('backupGlobals'));
        $this->assertSame($suites->count(), $processorManifest->getCallsCount('executor'));
        $this->assertSame($suites->count(), $processorManifest->getCallsCount('restoreGlobals'));
    }
}
