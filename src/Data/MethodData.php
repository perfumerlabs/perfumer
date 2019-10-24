<?php

namespace Perfumerlabs\Perfumer\Data;

use Perfumerlabs\Perfumer\ContractAnnotation\Set;
use Perfumerlabs\Perfumer\ContractAnnotation\Code;
use Perfumerlabs\Perfumer\ContractMethodAnnotation;
use Perfumerlabs\Perfumer\LocalVariable;
use Perfumerlabs\Perfumer\PerfumerException;
use Zend\Code\Generator\MethodGenerator;

final class MethodData
{
    /**
     * @var array
     */
    private $local_variables = [];

    /**
     * @var array
     */
    private $steps = [];

    /**
     * @var array
     */
    private $sets = [];

    /**
     * @var MethodGenerator
     */
    private $generator;

    /**
     * @var bool
     */
    private $_is_validating = false;

    /**
     * @var bool
     */
    private $_is_returning = false;

    public function __construct()
    {
        $this->generator = new MethodGenerator();
    }

    public function getLocalVariables(): array
    {
        return $this->local_variables;
    }

    public function addLocalVariable(LocalVariable $variable, $validate = true): void
    {
        if (!is_string($variable->name)) {
            throw new PerfumerException('Local variable must have a name.');
        }

        if ($variable->name[0] === '_') {
            return;
        }

        if ($this->hasLocalVariable($variable->name)) {
            if ($validate) {
                throw new PerfumerException('Local variable "' . $variable->name . '" is already used. If you want to redeclare it provide "redeclare=true" to the annotation.');
            }

            /** @var LocalVariable $local_variable */
            $local_variable = $this->local_variables[$variable->name];

            if ($local_variable->view !== $variable->view) {
                throw new PerfumerException('You can not redeclare local variable with different view type.');
            }

            return;
        }

        $this->local_variables[$variable->name] = $variable;
    }

    public function hasLocalVariable(string $name): bool
    {
        return isset($this->local_variables[$name]);
    }

    public function markLocalVariableAsValidatable(string $name, bool $valid_state = true): void
    {
        /** @var LocalVariable $variable */
        $variable = $this->requireLocalVariable($name);
        $variable->is_validatable = true;
        $variable->valid_state = $valid_state;
    }

    public function requireLocalVariable($name): LocalVariable
    {
        if (!$this->hasLocalVariable($name)) {
            throw new PerfumerException('Local variable "' . $name . '" is not added yet. Possibly, you have mistyped variable name or variable is not initialised yet.');
        }

        return $this->local_variables[$name];
    }

    public function getSteps(): array
    {
        return $this->steps;
    }

    public function setSteps(array $steps): void
    {
        $this->steps = $steps;
    }

    public function addStep(ContractMethodAnnotation $step): void
    {
        $this->steps[] = $step;
    }

    public function getSets(): array
    {
        return $this->sets;
    }

    public function setSets(array $sets): void
    {
        $this->sets = $sets;
    }

    public function addSet(Set $set): void
    {
        $this->sets[] = $set;
    }

    public function isValidating(): bool
    {
        return $this->_is_validating;
    }

    public function setIsValidating(bool $_is_validating): void
    {
        $this->_is_validating = $_is_validating;
    }

    public function getGenerator(): MethodGenerator
    {
        return $this->generator;
    }

    public function setGenerator(MethodGenerator $generator): void
    {
        $this->generator = $generator;
    }

    public function isReturning(): bool
    {
        return $this->_is_returning;
    }

    public function setIsReturning(bool $is_returning): void
    {
        $this->_is_returning = $is_returning;
    }

    public function generate(): string
    {
        $this->generateBody();

        return $this->generator->generate();
    }

    private function generateBody(): void
    {
        foreach ($this->steps as $step) {
            if ($step instanceof Code && !$step->valid) {
                $this->setIsValidating(true);
            }
        }

        $body = '';

        if ($this->isReturning()) {
            $body .= '$_return = null;';
        }

        if ($this->isValidating()) {
            $body .= '$_valid = true;' . PHP_EOL;
            $body .= '$_error = null;' . PHP_EOL;
        }

        foreach ($this->local_variables as $local_variable) {
            /** @var LocalVariable $local_variable */
            if ($local_variable->init) {
                $body .= '$' . $local_variable->name . ' = null;' . PHP_EOL;
            }

            if ($local_variable->is_validatable) {
                $body .= '$_valid_' . $local_variable->name . ' = true;' . PHP_EOL;
            }
        }

        $body .= PHP_EOL;

        /** @var Set $set */
        foreach ($this->sets as $set) {
            $body .= $this->generateStep($set);
        }

        $set_ids = [];

        foreach ($this->steps as $step) {
            if ($step instanceof Code) {
                if ($step instanceof Set) {
                    if (in_array($step->getId(), $set_ids)) {
                        continue;
                    }

                    $set_ids[] = $step->getId();
                }

                $body .= $this->generateStep($step);
            }
        }

        if ($this->isReturning()) {
            if ($this->isValidating()) {
                $body .= 'if (!$_valid && $_error) {' . PHP_EOL;
                $body .= '$_return = $_error;' . PHP_EOL;
                $body .= '}' . PHP_EOL;
            }

            $body .= 'return $_return;';
        }

        $this->generator->setBody($body);
    }

    private function generateStep(Code $step)
    {
        $body = '';

        if ($step->_before_code) {
            $body .= $step->_before_code . PHP_EOL . PHP_EOL;
        }

        $condition = null;
        $valid_initial_variable = null;

        if ($step->if || $step->unless) {
            $value = $step->if ?: $step->unless;

            if (!$step->valid) {
                $valid_initial_variable = '_valid_' . $value;
            }

            $condition = '$' . $value;

            if ($step->unless) {
                $condition = '!' . $condition;
            }
        }

        if ($step->_is_validatable && $this->isValidating()) {
            if ($step->valid && $condition) {
                $body .= 'if ($_valid && ' . $condition . ') {' . PHP_EOL;
            } elseif ($step->valid && !$condition) {
                $body .= 'if ($_valid) {' . PHP_EOL;
            } elseif (!$step->valid && !$valid_initial_variable) {
                $body .= 'if (!$_valid) {' . PHP_EOL;
            } elseif (!$step->valid && $valid_initial_variable) {
                $body .= 'if (!$' . $valid_initial_variable . ') {' . PHP_EOL;
            }
        } elseif ($condition) {
            $body .= 'if (' . $condition . ') {' . PHP_EOL;
        }

        $body .= $step->_code . PHP_EOL . PHP_EOL;

        if (($step->_is_validatable && $this->isValidating()) || $condition) {
            $body .= '}' . PHP_EOL . PHP_EOL;
        }

        if ($step->_after_code) {
            $body .= $step->_after_code . PHP_EOL . PHP_EOL;
        }

        return $body;
    }
}
