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
        /** @var Suggestible|ContainerBuilder $container */
        $container = new ContainerBuilder();
        $self = $this;
        Configurator::setCustomBehavior(
            $container,
            'get',
            function ($id) use ($self) {
                $self->assertSame('logger', $id);
            }
        );
        Configurator::storeInvokes($container, true);
        new Processor($container);
        $this->assertCount(1, Configurator::getAllInvokes($container));
    }

    /**
     * Test backup and restore $GLOBALS.
     */
    public function testBackUpGlobals()
    {
        $this->assertArrayNotHasKey('Unteist', $GLOBALS);
        $GLOBALS['Unteist'] = 'ok';
        /** @var Suggestible|ContainerBuilder $container */
        $container = new ContainerBuilder();
        Configurator::setCustomBehavior($container, 'get', null);
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
        /** @var Suggestible|ContainerBuilder $container */
        $container = new ContainerBuilder();
        $container->setParameter('suites', $suites);
        /** @var Suggestible $logger */
        $logger = new Logger('test');
        Configurator::setCustomBehavior($logger, 'info', null);
        $container->set('logger', $logger);
        /** @var Suggestible $dispatcher */
        $dispatcher = new EventDispatcher();
        Configurator::storeInvokes($dispatcher, true);
        $container->set('dispatcher', $dispatcher);
        /** @var Suggestible|Processor $processor */
        $processor = new Processor($container);
        Configurator::storeInvokes($processor, true);
        Configurator::setCustomBehavior($processor, 'executor', $executor);
        $this->assertSame($statusCode, $processor->run());

        $this->assertSame(
            [[EventStorage::EV_APP_STARTED], [EventStorage::EV_APP_FINISHED]],
            Configurator::getInvokes($dispatcher, 'dispatch'),
            'Dispatcher did not send application events.'
        );

        $this->assertSame(1, Configurator::getInvokesCount($processor, 'run'));
        $this->assertSame($suites->count(), Configurator::getInvokesCount($processor, 'backupGlobals'));
        $this->assertSame($suites->count(), Configurator::getInvokesCount($processor, 'executor'));
        $this->assertSame($suites->count(), Configurator::getInvokesCount($processor, 'restoreGlobals'));
    }
}
