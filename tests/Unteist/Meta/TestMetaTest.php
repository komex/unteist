<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Meta;

use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Unteist\Meta\TestMeta;

/**
 * Class TestMetaTest
 *
 * @package Tests\Unteist\Meta
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class TestMetaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Logger
     */
    protected static $logger;

    /**
     * Prepare case.
     */
    public static function setUpBeforeClass()
    {
        self::$logger = new Logger('test');
        self::$logger->pushHandler(new NullHandler());
    }

    /**
     * @return array
     */
    public function dpDataProvider()
    {
        return [
            [['dataProvider' => 'dp'], 'dp'],
            [['dataProvider' => 'method']],
            [['dataProvider' => true]],
            [['dataProvider' => []]],
            [['dataProvider' => '']],
            [['Dataprovider' => 'dp']],
            [[]],
        ];
    }

    /**
     * @param array $modifiers
     * @param string $expected
     *
     * @dataProvider dpDataProvider
     */
    public function testDataProvider(array $modifiers, $expected = null)
    {
        $meta = new TestMeta('class', 'method', $modifiers, self::$logger);
        $this->assertEquals($expected, $meta->getDataProvider());
    }

    /**
     * @return array
     */
    public function dpExpectedException()
    {
        return [
            [['expectedException' => 'Exception'], 'Exception'],
            [['expectedException' => true]],
            [['expectedException' => []]],
            [['expectedException' => '']],
            [['ExpectedException' => 'dp']],
            [[]],
        ];
    }

    /**
     * @param array $modifiers
     * @param string $expected
     *
     * @dataProvider dpExpectedException
     */
    public function testExpectedException(array $modifiers, $expected = null)
    {
        $meta = new TestMeta('class', 'method', $modifiers, self::$logger);
        $this->assertSame($expected, $meta->getExpectedException());
    }

    public function testExpectedExceptionMessage()
    {
        $meta = new TestMeta('class', 'method', ['expectedExceptionMessage' => 'Message'], self::$logger);
        $this->assertEmpty($meta->getExpectedExceptionMessage(), 'Message must be empty if exception does not set');

        $modifiers = ['expectedException' => 'Exception', 'expectedExceptionMessage' => true];
        $meta = new TestMeta('class', 'method', $modifiers, self::$logger);
        $this->assertEmpty($meta->getExpectedExceptionMessage(), 'Message may be only type of string');

        $modifiers = ['expectedException' => 'Exception', 'expectedExceptionMessage' => 'Message'];
        $meta = new TestMeta('class', 'method', $modifiers, self::$logger);
        $this->assertSame('Message', $meta->getExpectedExceptionMessage());
    }

    public function testExpectedExceptionCode()
    {
        $meta = new TestMeta('class', 'method', ['expectedExceptionCode' => 5], self::$logger);
        $this->assertNull($meta->getExpectedExceptionCode(), 'Code must be NULL if exception does not set');

        $modifiers = ['expectedException' => 'Exception', 'expectedExceptionCode' => true];
        $meta = new TestMeta('class', 'method', $modifiers, self::$logger);
        $this->assertNull($meta->getExpectedExceptionCode(), 'Code must be set');

        $modifiers = ['expectedException' => 'Exception', 'expectedExceptionCode' => 5];
        $meta = new TestMeta('class', 'method', $modifiers, self::$logger);
        $this->assertSame(5, $meta->getExpectedExceptionCode());
    }

    public function testDependsDefault()
    {
        $meta = new TestMeta('class', 'method', [], self::$logger);
        $this->assertEmpty($meta->getDependencies());
        $this->assertInternalType('array', $meta->getDependencies());
    }

    /**
     * @return array
     */
    public function dpDepends()
    {
        return [
            [true, []],
            ['!@*>', []],
            ['test1, test2, test1', ['test1', 'test2']],
            ['test1,method, test2#$, test1', ['test1', 'test2']],
            ['method, method', []],
        ];
    }

    /**
     * @param string $depends
     * @param array $expected
     *
     * @dataProvider dpDepends
     */
    public function testDepends($depends, array $expected)
    {
        $meta = new TestMeta('class', 'method', ['depends' => $depends], self::$logger);
        $this->assertEquals($expected, $meta->getDependencies());
    }
}
