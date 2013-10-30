<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

use SebastianBergmann\Diff;

/**
 * Class EqualTo
 *
 * @package Unteist\Assert\MatcherInterface
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class EqualTo extends AbstractMatcher
{
    /**
     * @var mixed
     */
    protected $expected;

    /**
     * @param mixed $expected
     */
    public function __construct($expected)
    {
        $this->expected = $expected;
    }

    /**
     * @inheritdoc
     */
    protected function condition($actual)
    {
        return $actual == $this->expected;
    }

    /**
     * Format variable for output.
     *
     * @param mixed $variable
     *
     * @return string
     */
    protected function formatter($variable)
    {
        if (is_resource($variable)) {
            return '<resource>';
        } elseif (is_object($variable)) {
            return '(object) ' . get_class($variable);
        } else {
            return sprintf('(%s) %s', gettype($variable), var_export($variable, true));
        }
    }

    /**
     * Get difference of two variables.
     *
     * @param mixed $actual
     *
     * @return string
     */
    protected function getDiff($actual)
    {
        if (is_array($actual) or is_array($this->expected)) {
            $diff = new Diff('--- Expected' . PHP_EOL . '+++ Actual' . PHP_EOL);

            // @todo Remove Diff class, make normal diff instrument.
            return $diff->diff(var_export($this->expected, true), var_export($actual, true));
        } else {
            return 'expected ' . $this->formatter($this->expected) . ', but given ' . $this->formatter($actual);
        }
    }

    /**
     * Get description for error output.
     *
     * @param mixed $actual
     *
     * @return string
     */
    protected function getFailDescription($actual)
    {
        return 'variables are equals:' . PHP_EOL . $this->getDiff($actual);
    }
}
