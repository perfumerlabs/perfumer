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
class SharedClassCall extends ClassCall
{
    public function onCreate(): void
    {
        $name = str_replace('\\', '_', $this->_class);

        $this->_expression = '$this->get_' . $name . '()->' . $this->_method;

        parent::onCreate();
    }

    public function onBuild(): void
    {
        parent::onBuild();

        $this->getBaseClassData()->addSharedClass($this->_class);
    }
}
