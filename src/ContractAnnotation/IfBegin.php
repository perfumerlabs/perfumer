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
class IfBegin extends Code
{
    public function __construct(
        public $name = null,
        ...$args
    )
    {
        parent::__construct(...$args);
    }

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
