<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class Out extends Code
{
    /**
     * @var string
     */
    public $name;

    public function onAnalyze(): void
    {
        parent::onAnalyze();

        $this->getMethodData()->requireLocalVariable($this->name);
    }

    public function onBuild(): void
    {
        parent::onBuild();

        $code = '$_return = $' . $this->name . ';';

        $this->_code = $code;

        $this->getMethodData()->setIsReturning(true);
    }
}
