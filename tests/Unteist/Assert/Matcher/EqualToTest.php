<?php
/**
 * This file is a part of unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Assert\Matcher;

use Unteist\Assert\Matcher\EqualTo;

/**
 * Class EqualToTest
 *
 * @package Tests\Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class EqualToTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function dpGoodWay()
    {
        return [
            [5, 5],
            [true, true],
            [false, false],
            ['str', 'str'],
            [['a' => 'b', 'c' => 'd'], ['c' => 'd', 'a' => 'b']],
            [0, 'string'],
            [4.2, '4.2 str'],
            [null, ''],
            [new \ArrayObject(), new \ArrayObject()],
        ];
    }

    /**
     * @return array
     */
    public function dpBadWay()
    {
        return [
            [-7, -3],
            [0, 0.2],
            ['str1', 'str2'],
            [['a' => 'b', 'c' => 'd'], ['d' => 'c', 'b' => 'a']],
            [null, 'f'],
        ];
    }

    /**
     * @param mixed $actual
     * @param mixed $expected
     *
     * @dataProvider dpGoodWay
     */
    public function testGoodWay($actual, $expected)
    {
        $class = new EqualTo($expected);
        $class->match($actual);
    }

    /**
     * @param mixed $actual
     * @param mixed $expected
     *
     * @dataProvider dpBadWay
     * @expectedException \Unteist\Exception\TestFailException
     * @expectedExceptionMessage Failed asserting that variables are equals:
     */
    public function testBadWay($actual, $expected)
    {
        $class = new EqualTo($expected);
        $class->match($actual);
    }

    /**
     * @return array
     */
    public function dpFormatter()
    {
        return [
            ['str', "(string) 'str'"],
            [-34, '(integer) -34'],
            [.0545, '(double) 0.0545'],
            [false, '(boolean) false'],
            [$this, '(object) Tests\\Unteist\\Assert\\Matcher\\EqualToTest'],
            [null, '(NULL) NULL'],
        ];
    }

    /**
     * Test formatter output.
     *
     * @param mixed $variable
     * @param string $expected
     *
     * @dataProvider dpFormatter
     */
    public function testFormatter($variable, $expected)
    {
        $method = new \ReflectionMethod('Unteist\\Assert\\Matcher\\EqualTo', 'formatter');
        $method->setAccessible(true);
        $this->assertSame($expected, $method->invoke(new EqualTo(null), $variable), 'Invalid assertion output.');
    }

    /**
     * Test diff output without arrays.
     */
    public function testGetDiffWithoutArray()
    {
        $object = new EqualTo('expected');
        $method = new \ReflectionMethod('Unteist\\Assert\\Matcher\\EqualTo', 'getDiff');
        $method->setAccessible(true);
        $this->assertSame(
            "expected (string) 'expected', but given (string) 'actual'",
            $method->invoke($object, 'actual'),
            'Invalid variables diff output.'
        );
    }

    /**
     * @return array
     */
    public function dpGetDiffWithArray()
    {
        return [
            ['expected', ['key' => 'value']],
            [['key' => 'value'], 'actual'],
        ];
    }

    /**
     * @param mixed $expected
     * @param mixed $actual
     *
     * @dataProvider dpGetDiffWithArray
     */
    public function testGetDiffWithArray($expected, $actual)
    {
        $object = new EqualTo($expected);
        $method = new \ReflectionMethod('Unteist\\Assert\\Matcher\\EqualTo', 'getDiff');
        $method->setAccessible(true);
        $this->assertStringStartsWith(
            '--- Expected' . PHP_EOL . '+++ Actual',
            $method->invoke($object, $actual),
            'getDiff() must use Diff object if there is an array.'
        );
    }
}
