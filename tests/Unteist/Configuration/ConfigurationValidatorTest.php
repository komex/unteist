<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Configuration;

use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\NodeInterface;
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
        $section = $this->getSection('configContextSection');
        $node = $section->getNode(true);
        $this->assertSame('context', $node->getName());
        $this->assertFalse($node->hasDefaultValue());
        $this->assertFalse($node->isRequired());

        $section->addDefaultsIfNotSet();
        $node = $section->getNode(true);
        $this->assertTrue($node->hasDefaultValue());
        $this->assertFalse($node->isRequired());
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
        $section = $this->getSection('configFiltersSection');
        $node = $section->getNode(true);
        $this->assertSame('filters', $node->getName());
        $this->assertFalse($node->hasDefaultValue());
        $this->assertFalse($node->isRequired());

        $section->addDefaultsIfNotSet();
        $node = $section->getNode(true);
        $this->assertTrue($node->hasDefaultValue());
        $this->assertFalse($node->isRequired());

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
        $node = $this->getNode('configLoggerSection', 'logger');
        $this->assertTrue($node->hasDefaultValue());
        /** @var array $defaults */
        $defaults = $node->getDefaultValue();
        $this->assertInternalType('array', $defaults);
        $this->assertEmpty($defaults);
    }

    /**
     * Test default source configuration.
     */
    public function testSourceSection()
    {
        $section = $this->getSection('configSourceSection');
        $node = $section->getNode(true);
        $this->assertSame('source', $node->getName());
        $this->assertTrue($node->hasDefaultValue());
        $default = $node->getDefaultValue();
        $this->assertInternalType('array', $default, 'Source section must be an array.');
        $this->assertEmpty($default, 'By default source sections does not set.');

        $section->addDefaultChildrenIfNoneSet();
        $sources = $section->getNode(true)->getDefaultValue();
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
        $node = $this->getNode('configSourceSection', 'source');
        $this->assertTrue($node->hasDefaultValue());
        $this->assertSame([], $node->getDefaultValue(), 'By default source sections does not set.');
        $node->finalize([]);
    }

    /**
     * Test default config tree definition.
     */
    public function testGetConfigTreeBuilder()
    {
        /** @var ArrayNode $node */
        $node = self::$validator->getConfigTreeBuilder()->buildTree();
        $this->assertEquals('unteist', $node->getName());
        $this->assertFalse($node->hasDefaultValue());
        $this->assertFalse($node->isRequired());
        /** @var NodeInterface[] $children */
        $children = $node->getChildren();
        $this->assertCount(8, $children);
        $this->assertArrayHasKey('processes', $children);
        $this->assertArrayHasKey('groups', $children);
        $this->assertArrayHasKey('context', $children);
        $this->assertArrayHasKey('filters', $children);
        $this->assertArrayHasKey('logger', $children);
        $this->assertArrayHasKey('suites', $children);
        $this->assertArrayHasKey('bootstrap', $children);
        $this->assertArrayHasKey('source', $children);
        $this->assertSame(1, $children['processes']->getDefaultValue());
        $this->assertSame([], $children['groups']->getDefaultValue(), 'By default group filter is switched off.');
    }

    public function testSuitesSection()
    {
        /** @var ArrayNode $node */
        $node = $this->getNode('configSuitesSection', 'suites');
        $this->assertTrue($node->hasDefaultValue());
        /** @var array $defaults */
        $defaults = $node->getDefaultValue();
        $this->assertInternalType('array', $defaults);
        $this->assertEmpty($defaults);
    }

    /**
     * @return array
     */
    public function dpProcesses()
    {
        return [[1], [2], [9], [10]];
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
                ['logger' => true],
                'Invalid type for path "unteist.logger". Expected array, but got boolean'
            ],
            [
                ['logger' => ''],
                'Invalid type for path "unteist.logger". Expected array, but got string'
            ],
            [
                ['logger' => []],
                'The path "unteist.logger" should have at least 1 element(s) defined.'
            ],
            [
                ['logger' => ['']],
                'The path "unteist.logger.0" cannot contain an empty value, but got "".'
            ],
            [
                ['suites' => true],
                'Invalid type for path "unteist.suites". Expected array, but got boolean'
            ],
            [
                ['suites' => ''],
                'Invalid type for path "unteist.suites". Expected array, but got string'
            ],
            [
                ['suites' => []],
                'The path "unteist.suites" should have at least 1 element(s) defined.'
            ],
            [
                ['suites' => ['']],
                'Invalid type for path "unteist.suites.0". Expected array, but got string'
            ],
            [
                ['source' => true],
                'Invalid type for path "unteist.source". Expected array, but got boolean'
            ],
            [
                ['source' => ''],
                'Invalid type for path "unteist.source". Expected array, but got string'
            ],
            [
                ['source' => []],
                'The path "unteist.source" should have at least 1 element(s) defined.'
            ],
            [
                ['source' => ['']],
                'Invalid type for path "unteist.source.0". Expected array, but got string'
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
     * @param string $method
     *
     * @return ArrayNodeDefinition
     */
    private function getSection($method)
    {
        $method = new \ReflectionMethod(self::$validator, $method);
        $method->setAccessible(true);
        $node = new ArrayNodeDefinition('root');

        return $method->invoke(self::$validator, $node);
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
        $node = $this->getSection($method)->getNode(true);
        $this->assertEquals($name, $node->getName());

        return $node;
    }
}
