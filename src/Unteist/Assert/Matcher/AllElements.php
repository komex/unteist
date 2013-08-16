<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

/**
 * Class AllElements
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class AllElements extends AbstractMatcher
{
    /**
     * @var int
     */
    protected $number;
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
        foreach ($actual as $number => $value) {
            $this->number = $number;
            if (!$this->expected->condition($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function fail($actual, $message)
    {
        $formatted = (empty($message) ? '' : $message . PHP_EOL);
        $formatted .= sprintf(
            'Completiotion failed on element #%d of %d',
            ($this->number + 1),
            count($actual)
        );
        $this->expected->fail($actual, $formatted);
    }
}
