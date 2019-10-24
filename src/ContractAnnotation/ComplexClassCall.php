<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class ComplexClassCall extends ClassCall
{
    /**
     * @var string
     */
    public $_instance;

    public function onCreate(): void
    {
        $this->_expression = $this->_instance . $this->_method;

        parent::onCreate();
    }
}
