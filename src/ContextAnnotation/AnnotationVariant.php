<?php

namespace Perfumerlabs\Perfumer\ContextAnnotation;

use Perfumerlabs\Perfumer\ContextClassAnnotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class AnnotationVariant extends ContextClassAnnotation
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var array
     */
    public $options = [];

    /**
     * @var bool
     */
    public $skip = false;
}
