<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class ThisCall extends Expression
{
    /**
     * @var string
     */
    public $_method;

    public function onCreate(): void
    {
        $this->_expression = '$this->' . $this->_method;

        parent::onCreate();
    }
}
