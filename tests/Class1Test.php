<?php

namespace Unteist\TestCaseTest;

use Unteist\Assert\Assert;
use Unteist\TestCase;

/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */
class Class1Test extends TestCase
{
    public function testIncomplete()
    {
        $this->markAsIncomplete('incomplete');
    }

    /**
     * @depends testOK
     */
    public function testNo()
    {
        $a = null;
        Assert::isNull($a);
    }


    public function testOK()
    {
        $this->markAsFail('ff');
    }
}
