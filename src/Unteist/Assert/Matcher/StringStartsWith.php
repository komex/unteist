<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

/**
 * Class StringStartsWith
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class StringStartsWith extends AbstractMatcher
{
    /**
     * @var string
     */
    protected $expected;

    /**
     * @param string $expected
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
        return strpos($actual, $this->expected) === 0;
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
        return $this->export($actual) . ' starts with ' . $this->export($this->expected);
    }
}
