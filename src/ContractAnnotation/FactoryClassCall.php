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
class FactoryClassCall extends ClassCall
{
    public function onCreate(): void
    {
        if ($this->_class[0] !== '\\') {
            $this->_class = '\\' . $this->_class;
        }

        $this->_expression = '(new ' . $this->_class . '())->' . $this->_method;

        parent::onCreate();
    }
}
