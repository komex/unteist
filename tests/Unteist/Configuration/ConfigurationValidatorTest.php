<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Processor;
use Unteist\Configuration\ConfigurationValidator;

/**
 * Class ConfigurationValidatorTest
 *
 * @package Tests\Unteist\Configuration
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class ConfigurationValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Processor
     */
    protected static $processor;
    /**
     * @var ConfigurationValidator
     */
    protected static $validator;

    /**
     * Precondition.
     */
    public static function setUpBeforeClass()
    {
        self::$processor = new Processor();
        self::$validator = new ConfigurationValidator();
    }

    /**
     * Test default context configuration.
     */
    public function testContextSection()
    {
        $node = $this->getNode('getContextSection', 'context');
        /** @var array $defaults */
        $defaults = $node->getDefaultValue();
        $this->assertCount(9, $defaults);
        $this->assertArrayHasKey('error', $defaults);
        $this->assertArrayHasKey('failure', $defaults);
        $this->assertArrayHasKey('incomplete', $defaults);
        $this->assertArrayHasKey('beforeCase', $defaults);
        $this->assertArrayHasKey('beforeTest', $defaults);
        $this->assertArrayHasKey('afterTest', $defaults);
        $this->assertArrayHasKey('afterCase', $defaults);
        $this->assertArrayHasKey('associations', $defaults);
        $this->assertArrayHasKey('levels', $defaults);
        $this->assertEquals('strategy.fail', $defaults['error']);
        $this->assertEquals('strategy.continue', $defaults['failure']);
        $this->assertEquals('strategy.continue', $defaults['incomplete']);
        $this->assertEquals('strategy.continue', $defaults['beforeCase']);
        $this->assertEquals('strategy.continue', $defaults['beforeTest']);
        $this->assertEquals('strategy.exception', $defaults['afterTest']);
        $this->assertEquals('strategy.exception', $defaults['afterCase']);
        $this->assertSame(['E_ALL'], $defaults['levels']);
        $this->assertInternalType('array', $defaults['associations']);
        $this->assertEmpty($defaults['associations']);
    }

    /**
     * Test default filter configuration.
     */
    public function testFilterSection()
    {
        $node = $this->getNode('getFiltersSection', 'filters');
        /** @var array $defaults */
        $defaults = $node->getDefaultValue();
        $this->assertCount(2, $defaults);
        $this->assertArrayHasKey('class', $defaults);
        $this->assertArrayHasKey('methods', $defaults);
        $this->assertSame(['filter.class.base'], $defaults['class']);
        $this->assertInternalType('array', $defaults['methods']);
        $this->assertEmpty($defaults['methods']);
    }

    /**
     * Test default logger configuration.
     */
    public function testLoggerSection()
    {
        $node = $this->getNode('getLoggerSection', 'logger');
        /** @var array $defaults */
        $defaults = $node->getDefaultValue();
        $this->assertCount(2, $defaults);
        $this->assertArrayHasKey('enabled', $defaults);
        $this->assertArrayHasKey('handlers', $defaults);
        $this->assertFalse($defaults['enabled']);
        $this->assertSame(['logger.handler.stream'], $defaults['handlers']);
    }

    /**
     * Test default source configuration.
     */
    public function testSourceSection()
    {
        $node = $this->getNode('getSourceSection', 'source');
        $sources = $node->finalize([[]]);
        $this->assertInternalType('array', $sources);
        $this->assertCount(1, $sources);
        $this->assertEquals(['in' => './tests', 'name' => '*Test.php', 'exclude' => [], 'notName' => []], $sources[0]);
    }

    /**
     * Test setup empty source configuration.
     *
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The path "source" should have at least 1 element(s) defined.
     */
    public function testEmptySourceSection()
    {
        $node = $this->getNode('getSourceSection', 'source');
        $this->assertSame([], $node->getDefaultValue(), 'By default source sections does not set.');
        $node->finalize([]);
    }

    /**
     * Test default config tree definition.
     */
    public function testGetConfigTreeBuilder()
    {
        $node = self::$validator->getConfigTreeBuilder()->buildTree();
        $this->assertEquals('unteist', $node->getName());
        $this->assertTrue($node->hasDefaultValue());
        $this->assertFalse($node->isRequired());
        /** @var array $defaults */
        $defaults = $node->getDefaultValue();
        $this->assertCount(8, $defaults);
        $this->assertArrayHasKey('processes', $defaults);
        $this->assertArrayHasKey('report_dir', $defaults);
        $this->assertArrayHasKey('listeners', $defaults);
        $this->assertArrayHasKey('groups', $defaults);
        $this->assertArrayHasKey('context', $defaults);
        $this->assertArrayHasKey('filters', $defaults);
        $this->assertArrayHasKey('logger', $defaults);
        $this->assertArrayHasKey('suites', $defaults);
        $this->assertSame(1, $defaults['processes']);
        $this->assertNull($defaults['report_dir'], 'By default report generation is switched off.');
        $this->assertSame([], $defaults['listeners'], 'By default additional listeners does not exists.');
        $this->assertSame([], $defaults['groups'], 'By default group filter is switched off.');
    }

    /**
     * Test default suites configuration.
     */
    public function testSuitesSection()
    {
        $node = $this->getNode('getSuitesSection', 'suites');
        /** @var array $defaults */
        $defaults = $node->finalize([[]]);
        $this->assertInternalType('array', $defaults);
        $this->assertCount(1, $defaults);
        $defaults = $defaults[0];
        $this->assertInternalType('array', $defaults);
        $this->assertCount(3, $defaults);
        $this->assertArrayHasKey('report_dir', $defaults);
        $this->assertArrayHasKey('groups', $defaults);
        $this->assertArrayHasKey('source', $defaults);
        $this->assertNull($defaults['report_dir'], 'By default report generation is switched off.');
        $this->assertSame([], $defaults['source'], 'By default source does not set.');
        $this->assertSame([], $defaults['groups'], 'By default group filter is switched off.');
    }

    /**
     * @return array
     */
    public function dpProcesses()
    {
        return [
            [1],
            [2],
            [9],
            [10],
        ];
    }

    /**
     * @param int $num
     *
     * @dataProvider dpProcesses
     */
    public function testProcesses($num)
    {
        $node = self::$validator->getConfigTreeBuilder()->buildTree();
        /** @var array $defaults */
        $defaults = $node->finalize(['processes' => $num]);
        $this->assertEquals($num, $defaults['processes']);
    }

    /**
     * @return array
     */
    public function dpIncorrectValues()
    {
        return [
            [
                ['processes' => 11],
                'The value 11 is too big for path "unteist.processes". Should be less than or equal to 10'
            ],
            [
                ['processes' => 0],
                'The value 0 is too small for path "unteist.processes". Should be greater than or equal to 1'
            ],
            [
                ['processes' => 'abc'],
                'Invalid type for path "unteist.processes". Expected int, but got string.'
            ],
            [
                ['report_dir' => ''],
                'The path "unteist.report_dir" cannot contain an empty value, but got "".'
            ],
            [
                ['report_dir' => false],
                'The path "unteist.report_dir" cannot contain an empty value, but got false.'
            ],
            [
                ['report_dir' => []],
                'Invalid type for path "unteist.report_dir". Expected scalar, but got array.'
            ],
            [
                ['listeners' => []],
                'The path "unteist.listeners" should have at least 1 element(s) defined.'
            ],
            [
                ['listeners' => ['']],
                'The path "unteist.listeners.0" cannot contain an empty value, but got "".'
            ],
            [
                ['listeners' => true],
                'Invalid type for path "unteist.listeners". Expected array, but got boolean'
            ],
            [
                ['groups' => []],
                'The path "unteist.groups" should have at least 1 element(s) defined.'
            ],
            [
                ['groups' => ['']],
                'The path "unteist.groups.0" cannot contain an empty value, but got "".'
            ],
            [
                ['groups' => true],
                'Invalid type for path "unteist.groups". Expected array, but got boolean'
            ],
            [
                ['logger' => ['handlers' => false]],
                'Invalid type for path "unteist.logger.handlers". Expected array, but got boolean'
            ],
            [
                ['logger' => ['handlers' => []]],
                'The path "unteist.logger.handlers" should have at least 1 element(s) defined.'
            ],
            [
                ['logger' => ['handlers' => ['']]],
                'The path "unteist.logger.handlers.0" cannot contain an empty value, but got "".'
            ],
        ];
    }

    /**
     * Test behavior with incorrect values.
     *
     * @param array $value
     * @param string $message
     *
     * @dataProvider dpIncorrectValues
     */
    public function testIncorrectValues(array $value, $message)
    {
        $node = self::$validator->getConfigTreeBuilder()->buildTree();
        $this->setExpectedException(
            '\\Symfony\\Component\\Config\\Definition\\Exception\\InvalidConfigurationException',
            $message
        );
        $node->finalize($value);
    }

    /**
     * Get node definition from method.
     *
     * @param string $method Method name
     * @param string $name Root section name
     *
     * @return \Symfony\Component\Config\Definition\NodeInterface
     */
    private function getNode($method, $name)
    {
        $method = new \ReflectionMethod(self::$validator, $method);
        $method->setAccessible(true);
        /** @var ArrayNodeDefinition $section */
        $section = $method->invoke(self::$validator);
        $node = $section->getNode(true);
        $this->assertEquals($name, $node->getName());
        $this->assertTrue($node->hasDefaultValue());
        $this->assertFalse($node->isRequired());

        return $node;
    }
}
