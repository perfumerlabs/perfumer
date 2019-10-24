<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

use Doctrine\Common\Annotations\Annotation\Target;
use Perfumerlabs\Perfumer\ContractClassAnnotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
class After extends ContractClassAnnotation
{
    /**
     * @var array
     */
    public $steps;
}
