<?php

namespace Perfumerlabs\Perfumer\ContextAnnotation;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Perfumerlabs\Perfumer\ContextMethodAnnotation;

/**
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("METHOD")
 */
#[\Attribute(
    \Attribute::TARGET_METHOD
)]
class Returns extends ContextMethodAnnotation
{
    public function __construct(
        public $names = null,
        public $assoc = true
    )
    {
    }
}
