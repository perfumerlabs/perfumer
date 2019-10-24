<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class PropertyCall extends Expression
{
    /**
     * @var string
     */
    public $_property;

    /**
     * @var string
     */
    public $_method;

    public function onCreate(): void
    {
        $this->_expression = '$this->' . $this->_property . '->' . $this->_method;

        parent::onCreate();
    }
}
