<?php

namespace Perfumerlabs\Perfumer\ContextAnnotation;

/**
 * @Annotation
 * @Target("METHOD")
 */
class SkipAnnotation extends AnnotationVariant
{
    public function onCreate(): void
    {
        $this->skip = true;

        parent::onCreate();
    }
}
