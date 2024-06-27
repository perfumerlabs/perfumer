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
class Out extends Code
{
    public function __construct(
        public $name = null,
        ...$args
    )
    {
        parent::__construct(...$args);
    }

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
