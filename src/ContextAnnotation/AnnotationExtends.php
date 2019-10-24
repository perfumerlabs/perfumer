<?php

namespace Perfumerlabs\Perfumer\ContextAnnotation;

use Perfumerlabs\Perfumer\ContextClassAnnotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class AnnotationExtends extends ContextClassAnnotation
{
    /**
     * @var string
     */
    public $class;
}
