<?php

namespace Perfumerlabs\Perfumer\ContextAnnotation;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
/**
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 */
#[\Attribute(
    \Attribute::TARGET_CLASS
)]
class GenerateNoAnnotations extends AnnotationVariant
{
    public function onCreate(): void
    {
        $this->skip = true;

        parent::onCreate();
    }
}
