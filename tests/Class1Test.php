<?php

namespace Unteist\TestCaseTest;

use Unteist\Assert\Assert;
use Unteist\TestCase;

/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <komexx@gmail.com>
 */
class Class1Test extends TestCase
{

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
        Assert::fail('ff');
    }
}
