<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class ObjectCall extends Expression
{
    /**
     * @var string
     */
    public $_object;

    /**
     * @var string
     */
    public $_method;

    public function onCreate(): void
    {
        $this->_expression = '$' . $this->_object . '->' . $this->_method;

        parent::onCreate();
    }
}
