<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;
use Perfumerlabs\Perfumer\ContractClassAnnotation;

/**
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 */
#[\Attribute(
    \Attribute::TARGET_CLASS
)]
class After extends ContractClassAnnotation
{
    /**
     * @var array
     */
    public $steps;
}
