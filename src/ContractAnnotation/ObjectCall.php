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
class ObjectCall extends Expression
{
    public function __construct(
        public $_object = null,
        public $_method = null,
        ...$args
    )
    {
        parent::__construct(...$args);
    }

    public function onCreate(): void
    {
        $this->_expression = '$' . $this->_object . '->' . $this->_method;

        parent::onCreate();
    }
}
