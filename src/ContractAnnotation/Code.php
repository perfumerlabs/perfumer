<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

use Perfumerlabs\Perfumer\ContractMethodAnnotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class Code extends ContractMethodAnnotation
{
    /**
     * @var string
     */
    public $_code;

    /**
     * @var string
     */
    public $_before_code;

    /**
     * @var string
     */
    public $_after_code;

    /**
     * @var string
     */
    public $if;

    /**
     * @var string
     */
    public $unless;

    /**
     * @var bool
     */
    public $valid = true;

    /**
     * @var bool
     */
    public $_is_validatable = true;

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
