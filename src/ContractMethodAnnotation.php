<?php

namespace Perfumerlabs\Perfumer;

use Perfumerlabs\Perfumer\Data\MethodData;

abstract class ContractMethodAnnotation extends ContractAnnotation
{
    /**
     * @var MethodData
     */
    private $_method_data;

    public function getMethodData(): ?MethodData
    {
        return $this->_method_data;
    }

    public function setMethodData(MethodData $method_data): void
    {
        $this->_method_data = $method_data;
    }
}
