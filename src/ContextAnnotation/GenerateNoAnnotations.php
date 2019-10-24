<?php

namespace Perfumerlabs\Perfumer\ContextAnnotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
class GenerateNoAnnotations extends AnnotationVariant
{
    public function onCreate(): void
    {
        $this->skip = true;

        parent::onCreate();
    }
}
