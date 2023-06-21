<?php

namespace Perfumerlabs\Perfumer;

use Perfumerlabs\Perfumer\Data\BaseClassData;
use Perfumerlabs\Perfumer\Data\BaseTestData;
use Perfumerlabs\Perfumer\Data\ClassData;
use Perfumerlabs\Perfumer\Data\TestData;

abstract class ContractAnnotation extends Annotation
{
    /**
     * @var \ReflectionClass
     */
    private $_reflection_class;

    /**
     * @var \ReflectionMethod
     */
    private $_reflection_method;

    /**
     * @var BaseClassData
     */
    private $_base_class_data;

    /**
     * @var ClassData
     */
    private $_class_data;

    /**
     * @var BaseTestData
     */
    private $_base_test_data;

    /**
     * @var TestData
     */
    private $_test_data;

    private $_options = [];

    public function onCreate(): void
    {
    }

    public function onAnalyze(): void
    {
    }

    public function onBuild(): void
    {
    }

    public function getBaseClassData(): BaseClassData
    {
        return $this->_base_class_data;
    }

    public function setBaseClassData(BaseClassData $base_class_data): void
    {
        $this->_base_class_data = $base_class_data;
    }

    public function getClassData(): ?ClassData
    {
        return $this->_class_data;
    }

    public function setClassData(ClassData $class_data): void
    {
        $this->_class_data = $class_data;
    }

    public function getBaseTestData(): BaseTestData
    {
        return $this->_base_test_data;
    }

    public function setBaseTestData(BaseTestData $base_test_data): void
    {
        $this->_base_test_data = $base_test_data;
    }

    public function getTestData(): ?TestData
    {
        return $this->_test_data;
    }

    public function setTestData(TestData $test_data): void
    {
        $this->_test_data = $test_data;
    }

    public function getReflectionClass(): ?\ReflectionClass
    {
        return $this->_reflection_class;
    }

    public function setReflectionClass(\ReflectionClass $reflection_class): void
    {
        $this->_reflection_class = $reflection_class;
    }

    public function getReflectionMethod(): ?\ReflectionMethod
    {
        return $this->_reflection_method;
    }

    public function setReflectionMethod(\ReflectionMethod $reflection_method): void
    {
        $this->_reflection_method = $reflection_method;
    }

    public function getOptions(): array
    {
        return $this->_options;
    }

    public function setOptions(array $_options): void
    {
        $this->_options = $_options;
    }
}
