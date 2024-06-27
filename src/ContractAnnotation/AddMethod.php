<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Perfumerlabs\Perfumer\ContractMethodAnnotation;

/**
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("METHOD")
 */
#[\Attribute(
    \Attribute::TARGET_METHOD
)]
class AddMethod extends ContractMethodAnnotation
{
}
