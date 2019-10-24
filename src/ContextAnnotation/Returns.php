<?php

namespace Perfumerlabs\Perfumer\ContextAnnotation;

use Perfumerlabs\Perfumer\ContextMethodAnnotation;

/**
 * @Annotation
 * @Target("METHOD")
 */
class Returns extends ContextMethodAnnotation
{
    /**
     * @var array
     */
    public $names;

    /**
     * @var bool
     */
    public $assoc = true;
}
