<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Processor;

use Delusion\Configurator;
use Delusion\Suggestible;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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
}
