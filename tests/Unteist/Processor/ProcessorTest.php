<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Processor;

use Influence\RemoteControlUtils as RC;
use Influence\ReturnStrategy\Callback;
use Influence\ReturnStrategy\Value;
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
        $manifest = RC::getObject($container)->getMethod('get');
        $manifest->setLog(true);
        $self = $this;
        $manifest->setValue(
            new Callback(
                function ($id) use ($self) {
                    $self->assertSame('logger', $id);
                }
            )
        );
        new Processor($container);
        $this->assertCount(1, $manifest->getLogs());
        RC::removeObject($container);
    }

    /**
     * Test backup and restore $GLOBALS.
     */
    public function testBackUpGlobals()
    {
        $this->assertArrayNotHasKey('Unteist', $GLOBALS);
        $GLOBALS['Unteist'] = 'ok';
        $container = new ContainerBuilder();
        RC::getObject($container)->getMethod('get')->setValue(new Value(null));
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
        RC::removeObject($container);
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
        RC::getObject($logger)->getMethod('info')->setValue(new Value(null));
        $container->set('logger', $logger);
        $dispatcher = new EventDispatcher();
        $dispatcherManifest = RC::getObject($dispatcher)->getMethod('dispatch');
        $dispatcherManifest->setLog(true);
        $container->set('dispatcher', $dispatcher);
        $processor = new Processor($container);

        $processorManifest = RC::getObject($processor);
        $processorRunManifest = $processorManifest->getMethod('run');
        $processorRunManifest->setLog(true);
        $processorBackupManifest = $processorManifest->getMethod('backupGlobals');
        $processorBackupManifest->setLog(true);
        $processorRestoreManifest = $processorManifest->getMethod('restoreGlobals');
        $processorRestoreManifest->setLog(true);

        $processorExecutorManifest = $processorManifest->getMethod('executor');
        $processorExecutorManifest->setLog(true);
        $processorExecutorManifest->setValue(new Value($executor));
        $this->assertSame($statusCode, $processor->run());

        $this->assertSame(
            [[EventStorage::EV_APP_STARTED], [EventStorage::EV_APP_FINISHED]],
            $dispatcherManifest->getLogs(),
            'Dispatcher did not send application events.'
        );

        $this->assertCount(1, $processorRunManifest->getLogs());
        $this->assertCount($suites->count(), $processorBackupManifest->getLogs());
        $this->assertCount($suites->count(), $processorExecutorManifest->getLogs());
        $this->assertCount($suites->count(), $processorRestoreManifest->getLogs());

        RC::removeObject($logger);
        RC::removeObject($dispatcher);
        RC::removeObject($processor);
    }
}
