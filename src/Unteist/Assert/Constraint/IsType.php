<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Constraint;

use SebastianBergmann\Exporter\Exporter;


/**
 * Class IsType
 *
 * @package Unteist\Assert\Constraint
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class IsType implements ConstraintInterface
{
    /**
     * @var mixed
     */
    protected $element;
    /**
     * @var string
     */
    protected $expected_type;
    /**
     * @var bool
     */
    protected $inverse;

    /**
     * @param mixed $element
     * @param string $expected_type boolean, integer, double, string, array, object, resource, null
     * @param bool $inverse
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($element, $expected_type, $inverse = false)
    {
        $valid_types = ['boolean', 'integer', 'double', 'string', 'array', 'object', 'resource', 'null'];
        $expected_type = strtolower($expected_type);
        if (!in_array($expected_type, $valid_types)) {
            $valid_types = join(', ', $valid_types);
            throw new \InvalidArgumentException('Unknown expected type. It should be one of this: ' . $valid_types);
        }
        $this->element = $element;
        $this->expected_type = $expected_type;
        $this->inverse = $inverse;
    }

    /**
     * Check conditions.
     *
     * @return bool
     */
    public function matches()
    {
        $return = gettype($this->element) == $this->expected_type;
        if ($this->inverse) {
            $return = !$return;
        }

        return $return;
    }

    /**
     * Get a description of constraint.
     *
     * @return string
     */
    public function toString()
    {
        $exporter = new Exporter();

        return $exporter->export(
            $this->element
        ) . ' ' . (($this->inverse) ? 'is not' : 'is') . ' type of ' . $this->expected_type;
    }
}