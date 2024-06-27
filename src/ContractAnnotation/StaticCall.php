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
class StaticCall extends Expression
{
    public function __construct(
        public $_class = null,
        public $_method = null,
        ...$args
    )
    {
        parent::__construct(...$args);
    }

    public function onCreate(): void
    {
        if ($this->_class[0] !== '\\') {
            $this->_class = '\\' . $this->_class;
        }

        $this->_expression = $this->_class . '::' . $this->_method;

        parent::onCreate();
    }
}
