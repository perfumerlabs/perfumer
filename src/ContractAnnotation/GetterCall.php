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
class GetterCall extends Expression
{
    public function __construct(
        public $_getter = null,
        public $_method = null,
        ...$args
    )
    {
        parent::__construct(...$args);
    }

    public function onCreate(): void
    {
        $this->_expression = '$this->' . $this->_getter . '()->' . $this->_method;

        parent::onCreate();
    }
}
