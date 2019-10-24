<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
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
