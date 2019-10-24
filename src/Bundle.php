<?php

namespace Perfumerlabs\Perfumer;

use Perfumerlabs\Perfumer\Data\BaseClassData;
use Perfumerlabs\Perfumer\Data\BaseTestData;
use Perfumerlabs\Perfumer\Data\ClassData;
use Perfumerlabs\Perfumer\Data\TestData;

final class Bundle
{
    /**
     * @var BaseClassData[]
     */
    private $base_class_data = [];

    /**
     * @var ClassData[]
     */
    private $class_data = [];

    /**
     * @var BaseTestData[]
     */
    private $base_test_data = [];

    /**
     * @var TestData[]
     */
    private $test_data = [];

    /**
     * @return BaseClassData[]
     */
    public function getBaseClassData(): array
    {
        return $this->base_class_data;
    }

    /**
     * @param BaseClassData[] $base_class_data
     */
    public function setBaseClassData(array $base_class_data): void
    {
        $this->base_class_data = $base_class_data;
    }

    /**
     * @param BaseClassData $base_class
     */
    public function addBaseClassData(BaseClassData $base_class): void
    {
        $this->base_class_data[] = $base_class;
    }

    /**
     * @return ClassData[]
     */
    public function getClassData(): array
    {
        return $this->class_data;
    }

    /**
     * @param ClassData[] $class_data
     */
    public function setClassData(array $class_data): void
    {
        $this->class_data = $class_data;
    }

    /**
     * @param ClassData $class
     */
    public function addClassData(ClassData $class): void
    {
        $this->class_data[] = $class;
    }

    /**
     * @return BaseTestData[]
     */
    public function getBaseTestData(): array
    {
        return $this->base_test_data;
    }

    /**
     * @param BaseTestData[] $base_test_data
     */
    public function setBaseTestData(array $base_test_data): void
    {
        $this->base_test_data = $base_test_data;
    }

    /**
     * @param BaseTestData $base_test_data
     */
    public function addBaseTestData(BaseTestData $base_test_data): void
    {
        $this->base_test_data[] = $base_test_data;
    }

    /**
     * @return TestData[]
     */
    public function getTestData(): array
    {
        return $this->test_data;
    }

    /**
     * @param TestData[] $test_data
     */
    public function setTestData(array $test_data): void
    {
        $this->test_data = $test_data;
    }

    /**
     * @param TestData $test_data
     */
    public function addTestData(TestData $test_data): void
    {
        $this->test_data[] = $test_data;
    }
}
