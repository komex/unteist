<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

use Unteist\Exception\TestFailException;

/**
 * Class AnyValue
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class AnyValue extends AbstractMatcher
{
    /**
     * @var AbstractMatcher
     */
    protected $expected;

    /**
     * @param AbstractMatcher $expected
     */
    public function __construct(AbstractMatcher $expected)
    {
        $this->expected = $expected;
    }

    /**
     * Matcher condition.
     *
     * @param mixed $actual
     *
     * @throws \InvalidArgumentException If $actual variable not an array or instance of Traversable.
     * @return bool
     */
    protected function condition($actual)
    {
        if (!(is_array($actual) || ($actual instanceof \Traversable))) {
            throw new \InvalidArgumentException('Actual variable must be an array or instance of Traversable.');
        }
        foreach ($actual as $value) {
            if ($this->expected->condition($value) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    protected function fail($actual, $message)
    {
        $count = count($actual);
        $formatted = sprintf(
            'It was expected the successful completion of condition at least one of %d %s.',
            $count,
            ($count === 1 ? 'element' : 'elements')
        );
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
