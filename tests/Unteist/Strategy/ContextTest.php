<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Strategy;

use Unteist\Strategy\Context;
use Unteist\Strategy\TestFailStrategy;

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
        $this->context = new Context();
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
     * @expectedException \Unteist\Exception\TestFailException
     * @expectedExceptionMessage Test was marked as failure by chosen strategy
     */
    public function testUnexpectedExceptionWithAssociation()
    {
        $this->context->associateException('Exception', new TestFailStrategy());
        $this->context->onUnexpectedException(new \Exception('Message'));
    }
}
