<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
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
