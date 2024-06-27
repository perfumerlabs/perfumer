<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
/**
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
#[\Attribute(
    \Attribute::TARGET_METHOD |
    \Attribute::TARGET_CLASS |
    \Attribute::IS_REPEATABLE
)]
class SetFromInject extends Set
{
    public function onBuild(): void
    {
        parent::onBuild();

        $code = '$' . $this->name . ' = $this->get' . str_replace('_', '', ucwords($this->value, '_')) . '();';

        $this->_code = $code;

        $id = '_inject__' . $this->name . '__' . $this->value;

        $this->setId($id);
    }
}
