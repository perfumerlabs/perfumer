<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Perfumerlabs\Perfumer\LocalVariable;

/**
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
#[\Attribute(
    \Attribute::TARGET_METHOD |
    \Attribute::TARGET_CLASS |
    \Attribute::IS_REPEATABLE
)]
class Expression extends Code
{
    public function __construct(
        public $redeclare = false,
        public $_expression = null,
        public $_arguments = [],
        public $_return = null,
        ...$args
    )
    {
        parent::__construct(...$args);
    }

    public function onCreate(): void
    {
        if (!$this->valid) {
            $this->_return = '_error';
        }

        parent::onCreate();
    }

    public function onAnalyze(): void
    {
        parent::onAnalyze();

        if ($this->_arguments) {
            foreach ($this->_arguments as $argument) {
                $this->getMethodData()->requireLocalVariable($argument);
            }
        }

        if ($this->_return) {
            if (is_array($this->_return)) {
                if ($this->isAssociative($this->_return)) {
                    foreach ($this->_return as $key => $value) {
                        $variable = new LocalVariable();
                        $variable->name = $key;

                        $this->getMethodData()->addLocalVariable($variable, !$this->redeclare);
                    }
                } else {
                    foreach ($this->_return as $key) {
                        $variable = new LocalVariable();
                        $variable->name = $key;

                        $this->getMethodData()->addLocalVariable($variable, !$this->redeclare);
                    }
                }
            } elseif (is_string($this->_return)) {
                $variable = new LocalVariable();
                $variable->name = $this->_return;

                $this->getMethodData()->addLocalVariable($variable, !$this->redeclare);
            }
        }
    }

    public function onBuild(): void
    {
        parent::onBuild();

        $return_expression = '';
        $return_expression_after = '';

        if ($this->_return) {
            if (is_array($this->_return)) {
                if ($this->isAssociative($this->_return)) {
                    $return_expression = '$_tmp = ';

                    foreach ($this->_return as $key => $value) {
                        $return_expression_after .= sprintf('$%s = $_tmp[\'%s\'];', $key, $key) . PHP_EOL;
                    }

                    $return_expression_after .= '$_tmp = null;' . PHP_EOL;

                    foreach ($this->_return as $key => $value) {
                        $valid_expression = $this->validExpression($key);

                        if ($valid_expression) {
                            $return_expression_after .= 'if ($_valid) {' . PHP_EOL;
                            $return_expression_after .= $valid_expression . '$' . $key . ';' . PHP_EOL;
                            $return_expression_after .= '}' . PHP_EOL;
                        }
                    }
                } else {
                    $vars = array_map(function ($v) {
                        return '$' . $v;
                    }, $this->_return);

                    $return_expression = 'list(' . implode(', ', $vars) . ') = ';

                    foreach ($this->_return as $key) {
                        $valid_expression = $this->validExpression($key);

                        if ($valid_expression) {
                            $return_expression_after .= 'if ($_valid) {' . PHP_EOL;
                            $return_expression_after .= $valid_expression . '$' . $key . ';' . PHP_EOL;
                            $return_expression_after .= '}' . PHP_EOL;
                        }
                    }
                }
            } elseif (is_string($this->_return)) {
                $return_expression .= '$' . $this->_return . ' = ';

                $valid_expression = $this->validExpression($this->_return);

                if ($valid_expression) {
                    $return_expression_after .= $valid_expression . '$' . $this->_return . ';' . PHP_EOL;
                }
            } elseif ($this->_return === true) {
                $return_expression = '$_return = ';

                $this->getMethodData()->setIsReturning(true);
            }
        }

        $code = $return_expression . $this->_expression;

        $arguments_expression = '';

        if ($this->_arguments) {
            $vars = array_map(function ($v) {
                $variable = $this->getMethodData()->requireLocalVariable($v);

                return $variable->render();
            }, $this->_arguments);

            $arguments_expression = implode(', ', $vars);
        }

        $code = $code . '(' . $arguments_expression . ');';

        if ($return_expression_after) {
            $code .= PHP_EOL . $return_expression_after;
        }

        $this->_code = $code;
    }

    private function validExpression($key)
    {
        if ($key === '_error') {
            return '';
        }

        $variable = $this->getMethodData()->requireLocalVariable($key);

        if (!$variable->is_validatable) {
            return '';
        }

        return '$_valid = $_valid_' . $key . ' = ' . ($variable->valid_state === true ? '(bool) ' : '!');
    }

    private function isAssociative(array $array)
    {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }
}
