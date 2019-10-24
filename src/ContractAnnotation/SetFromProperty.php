<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class SetFromProperty extends Set
{
    public function onBuild(): void
    {
        parent::onBuild();

        $code = '$' . $this->name . ' = $this->' . $this->value . ';';

        $this->_code = $code;

        $id = '_property__' . $this->name . '__' . $this->value;

        $this->setId($id);
    }
}
