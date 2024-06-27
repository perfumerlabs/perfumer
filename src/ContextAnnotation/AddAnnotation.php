<?php

namespace Perfumerlabs\Perfumer\ContextAnnotation;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
/**
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("METHOD")
 */
#[\Attribute(
    \Attribute::TARGET_METHOD |
    \Attribute::IS_REPEATABLE
)]
class AddAnnotation extends AnnotationVariant
{
}
