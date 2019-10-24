<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class GetterCall extends Expression
{
    /**
     * @var string
     */
    public $_getter;

    /**
     * @var string
     */
    public $_method;

    public function onCreate(): void
    {
        $this->_expression = '$this->' . $this->_getter . '()->' . $this->_method;

        parent::onCreate();
    }
}
