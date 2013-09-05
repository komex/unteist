<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Strategy;

use Unteist\Strategy\Context;
use Unteist\Strategy\IncompleteTestStrategy;

/**
 * Class ContextTest
 *
 * @package Tests\Unteist\Strategy
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class ContextTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * Prepare test.
     */
    public function setUp()
    {
        $strategy = new IncompleteTestStrategy();
        $this->context = new Context($strategy, $strategy, $strategy);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Message
     */
    public function testUnexpectedExceptionWithoutAssociation()
    {
        $this->context->onUnexpectedException(new \Exception('Message'));
    }

    /**
     * @expectedException \Unteist\Exception\IncompleteTestException
     * @expectedExceptionMessage Test was marked as incomplete by chosen strategy
     */
    public function testUnexpectedExceptionWithAssociation()
    {
        $this->context->associateException('Exception', new IncompleteTestStrategy());
        $this->context->onUnexpectedException(new \Exception('Message'));
    }
}
