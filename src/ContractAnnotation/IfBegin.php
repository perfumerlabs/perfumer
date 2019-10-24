<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class IfBegin extends Code
{
    /**
     * @var string
     */
    public $name;

    public function onCreate(): void
    {
        $this->_is_validatable = false;

        parent::onCreate();
    }

    public function onAnalyze(): void
    {
        parent::onAnalyze();

        $this->getMethodData()->requireLocalVariable($this->name);
    }

    public function onBuild(): void
    {
        parent::onBuild();

        $code = 'if ($' . $this->name . ') {';

        $this->_code = $code;
    }
}
