<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Perfumerlabs\Perfumer\ContractMethodAnnotation;

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
class Code extends ContractMethodAnnotation
{
    public function __construct(
        public $_code = null,
        public $_before_code = null,
        public $_after_code = null,
        public $if = null,
        public $unless = null,
        public $valid = true,
        public $_is_validatable = true,
    )
    {
    }

    public function onAnalyze(): void
    {
        parent::onAnalyze();

        if ($this->if) {
            $this->getMethodData()->requireLocalVariable($this->if);

            if (!$this->valid) {
                $this->getMethodData()->markLocalVariableAsValidatable($this->if, false);
            }
        }

        if ($this->unless) {
            $this->getMethodData()->requireLocalVariable($this->unless);

            if (!$this->valid) {
                $this->getMethodData()->markLocalVariableAsValidatable($this->unless, true);
            }
        }
    }
}
