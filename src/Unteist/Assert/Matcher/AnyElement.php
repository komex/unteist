<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

/**
 * Class AnyElement
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class AnyElement extends AbstractMatcher
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
        $formatted = (empty($message) ? '' : $message . PHP_EOL);
        $count = count($actual);
        $formatted .= sprintf(
            'It was expected the successful completion of condition at least one of %d %s.',
            $count,
            ($count === 1 ? 'element' : 'elements')
        );
        parent::fail($actual, $formatted);
    }
}
