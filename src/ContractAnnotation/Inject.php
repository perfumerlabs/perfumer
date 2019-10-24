<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

use Doctrine\Common\Annotations\Annotation\Target;
use Perfumerlabs\Perfumer\ContractClassAnnotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Inject extends ContractClassAnnotation
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $type;

    public function onBuild(): void
    {
        parent::onBuild();

        $this->getBaseClassData()->addInjection($this->name, $this->type);
    }
}
