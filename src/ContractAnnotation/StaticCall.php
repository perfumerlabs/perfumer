<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class StaticCall extends Expression
{
    /**
     * @var string
     */
    public $_class;

    /**
     * @var string
     */
    public $_method;

    public function onCreate(): void
    {
        if ($this->_class[0] !== '\\') {
            $this->_class = '\\' . $this->_class;
        }

        $this->_expression = $this->_class . '::' . $this->_method;

        parent::onCreate();
    }
}
