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
    \Attribute::TARGET_CLASS |
    \Attribute::IS_REPEATABLE
)]
class Inject extends ContractClassAnnotation
{
    public function __construct(
        public $name = null,
        public $type = null,
    )
    {
    }

    public function onBuild(): void
    {
        parent::onBuild();

        $this->getBaseClassData()->addInjection($this->name, $this->type);
    }
}
