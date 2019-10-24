<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class SelfCall extends Expression
{
    /**
     * @var string
     */
    public $_method;

    public function onCreate(): void
    {
        $this->_expression = 'self::' . $this->_method;

        parent::onCreate();
    }
}
