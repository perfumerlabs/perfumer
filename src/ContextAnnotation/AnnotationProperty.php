<?php

namespace Perfumerlabs\Perfumer\ContextAnnotation;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Perfumerlabs\Perfumer\ContextClassAnnotation;

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
class AnnotationProperty extends ContextClassAnnotation
{
    public function __construct(
        public $name = null,
        public $value = null
    )
    {
    }
}
