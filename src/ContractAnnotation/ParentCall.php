<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class ParentCall extends Expression
{
    /**
     * @var string
     */
    public $_method;

    public function onCreate(): void
    {
        $this->_expression = 'parent::' . $this->_method;

        parent::onCreate();
    }
}
