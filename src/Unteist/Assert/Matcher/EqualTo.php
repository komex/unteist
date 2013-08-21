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
     * Get difference of two variables.
     *
     * @param mixed $actual
     *
     * @return string
     */
    protected function getDiff($actual)
    {
        $diff = new Diff('--- Expected' . PHP_EOL . '+++ Actual' . PHP_EOL);

        return trim($diff->diff(var_export($this->expected, true), var_export($actual, true)));
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
