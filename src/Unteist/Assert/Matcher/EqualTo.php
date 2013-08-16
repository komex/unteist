<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

use SebastianBergmann\Diff;
use Unteist\Exception\TestFailException;

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
     * @inheritdoc
     */
    protected function fail($actual, $message)
    {
        $diff = new Diff('--- Expected' . PHP_EOL . '+++ Actual' . PHP_EOL);
        $formatted = $diff->diff(var_export($this->expected, true), var_export($actual, true));
        if (!empty($message)) {
            $formatted = $message . PHP_EOL . $formatted;
        }
        throw new TestFailException($formatted);
    }

    /**
     * Get description for error output.
     *
     * @param mixed $actual
     *
     * @throws \BadMethodCallException
     */
    protected function getFailDescription($actual)
    {
        throw new \BadMethodCallException(sprintf('Method %s can\'t be called.'));
    }
}
