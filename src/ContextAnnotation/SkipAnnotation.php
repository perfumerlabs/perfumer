<?php

namespace Perfumerlabs\Perfumer\ContextAnnotation;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
/**
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("METHOD")
 */
#[\Attribute(
    \Attribute::TARGET_METHOD
)]
class SkipAnnotation extends AnnotationVariant
{
    public function onCreate(): void
    {
        $this->skip = true;

        parent::onCreate();
    }
}
