<?php

namespace Perfumerlabs\Perfumer\ContextAnnotation;

use Perfumerlabs\Perfumer\ContextClassAnnotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class AnnotationProperty extends ContextClassAnnotation
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $value;
}
