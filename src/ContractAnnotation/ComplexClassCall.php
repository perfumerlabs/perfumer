<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
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
class ComplexClassCall extends ClassCall
{
    public function __construct(
        public $_instance = null,
        ...$args
    )
    {
        parent::__construct(...$args);
    }

    public function onCreate(): void
    {
        $this->_expression = $this->_instance . $this->_method;

        parent::onCreate();
    }
}
