<?php

namespace Perfumer\Generator\StandardProject\Contract;

use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Perfumer\Generator\Directory;
use Perfumer\Generator\LaminasClass;
use Perfumer\Generator\Project;
use Perfumer\Generator\StandardProject\Context\TestFile;
use Perfumerlabs\Perfumer\Collection;
use Perfumerlabs\Perfumer\ContractAnnotation\AddMethod;
use Perfumerlabs\Perfumer\ContractAnnotation\After;
use Perfumerlabs\Perfumer\ContractAnnotation\Before;
use Perfumerlabs\Perfumer\ContractAnnotation\ClassCall;
use Perfumerlabs\Perfumer\ContractAnnotation\ImplementNoMethods;
use Perfumerlabs\Perfumer\ContractAnnotation\Set;
use Perfumerlabs\Perfumer\ContractAnnotation\SkipMethod;
use Perfumerlabs\Perfumer\ContractClassAnnotation;
use Perfumerlabs\Perfumer\ContractMethodAnnotation;
use Perfumerlabs\Perfumer\Data\BaseClassData;
use Perfumerlabs\Perfumer\Data\BaseTestData;
use Perfumerlabs\Perfumer\Data\ClassData;
use Perfumerlabs\Perfumer\Data\MethodData;
use Perfumerlabs\Perfumer\Data\TestData;
use Perfumerlabs\Perfumer\LocalVariable;

class BaseTestFile extends LaminasClass
{
    /**
     * @var \ReflectionClass
     */
    protected $reflectionClass;

    protected $contractPrefix;

    protected $contextPrefix;

    protected $classPrefix;

    protected $contractClass;

    protected $skip = false;

    protected $generated_annotation_directory;

    protected $namespacePrefix = 'Generated\\Tests\\';

    public function __construct(
        string $contractPrefix,
        string $contextPrefix,
        string $classPrefix,
        array $contractClass,
        Directory $generated_annotation_directory
    )
    {
        $this->contractPrefix = $contractPrefix;
        $this->contextPrefix = $contextPrefix;
        $this->classPrefix = $classPrefix;
        $this->contractClass = $contractClass;
        $this->reflectionClass = new \ReflectionClass($contractClass['class']);
        $this->generated_annotation_directory = $generated_annotation_directory;

        $path = $this->buildPath();

        parent::__construct($contractClass['class'], $path);
    }

    public function build(): string|bool
    {
        $this->buildGenerator();

        if ($this->skip) {
            return false;
        }

        return parent::build();
    }

    protected function buildPath(): string
    {
        $namespace = str_replace($this->contractPrefix, $this->classPrefix, $this->reflectionClass->getNamespaceName());
        $name = $this->reflectionClass->getShortName();

        $output_name = str_replace('\\', '/', trim(str_replace($this->namespacePrefix . $this->classPrefix, '', $namespace), '\\'));

        if ($output_name) {
            $output_name .= '/';
        }

        return $output_name . $name . '.php';
    }

    protected function buildGenerator(): void
    {
        $class = $this->contractClass['class'];

        $reflection_class = new \ReflectionClass($class);

        if ($this->contractClass['has_default_context']) {
            $this->generateContexts([$class . 'Context']);
        }

        $implement_all_methods = true;

        $implement_no_methods = Project::collectClassAnnotation($reflection_class, ImplementNoMethods::class);

        if ($implement_no_methods !== null) {
            $implement_all_methods = false;
        }

        $namespace = str_replace($this->contractPrefix, $this->classPrefix, $reflection_class->getNamespaceName());

        $test_data = new TestData();
        $test_generator = $test_data->getGenerator();
        $test_generator->setNamespaceName('Tests\\' . $namespace);
        $test_generator->setName($reflection_class->getShortName() . 'Test');
        $test_generator->setExtendedClass('Generated\\Tests\\' . $namespace . '\\' . $reflection_class->getShortName() . 'Test');

        $base_test_data = new BaseTestData();
        $base_test_generator = $base_test_data->getGenerator();
        $base_test_generator->setNamespaceName('Generated\\Tests\\' . $namespace);
        $base_test_generator->setAbstract(true);
        $base_test_generator->setName($reflection_class->getShortName() . 'Test');
        $base_test_generator->setExtendedClass('PHPUnit\\Framework\\TestCase');

        $reflection_test = new MethodGenerator();
        $reflection_test->setFinal(true);
        $reflection_test->setName('testSyntax');
        $reflection_test->setBody('new \\ReflectionClass(\\' . $namespace . '\\' . $reflection_class->getShortName() . '::class);');
        $base_test_generator->addMethodFromGenerator($reflection_test);

        $class_data = new ClassData();
        $class_generator = $class_data->getGenerator();
        $class_generator->setNamespaceName($namespace);
        $class_generator->setName($reflection_class->getShortName());
        $class_generator->setExtendedClass('\\Generated\\' . $namespace . '\\' . $reflection_class->getShortName());

        $base_class_data = new BaseClassData();
        $base_class_generator = $base_class_data->getGenerator();
        $base_class_generator->setAbstract(true);
        $base_class_generator->setNamespaceName('Generated\\' . $namespace);
        $base_class_generator->setName($reflection_class->getShortName());

        if ($reflection_class->isInterface()) {
            $base_class_generator->setImplementedInterfaces(array_merge($base_class_generator->getImplementedInterfaces(), ['\\' . $class]));
        } else {
            $base_class_generator->setExtendedClass('\\' . $class);
        }

        /** @var ContractClassAnnotation[] $class_annotations */
        $class_annotations = [];

        foreach ($this->getModules() as $module) {
            if ($module['regex'] === null || (!in_array($class, $module['exceptions']) && preg_match($module['regex'], $class))) {
                foreach ($module['annotations'] as $module_annotation) {
                    if ($module_annotation instanceof ContractClassAnnotation) {
                        $class_annotations[] = $module_annotation;
                    }
                }
            }
        }

        $reader_annotations = Project::getAnnotationReader()->getClassAnnotations($reflection_class);

        foreach ($reader_annotations as $reader_annotation) {
            if ($reader_annotation instanceof ContractClassAnnotation) {
                $class_annotations[] = $reader_annotation;
            }
        }

        foreach ($class_annotations as $annotation) {
            $annotation->setReflectionClass($reflection_class);
            $annotation->setBaseClassData($base_class_data);
            $annotation->setBaseTestData($base_test_data);
            $annotation->setClassData($class_data);
            $annotation->setTestData($test_data);

            $annotation->onCreate();
        }

        foreach ($class_annotations as $annotation) {
            $annotation->onAnalyze();
        }

        foreach ($class_annotations as $annotation) {
            $annotation->onBuild();
        }

        foreach ($reflection_class->getMethods() as $reflection_method) {
            $add_method = Project::collectMethodAnnotation($reflection_class, $reflection_method, AddMethod::class);
            $skip_method = Project::collectMethodAnnotation($reflection_class, $reflection_method, SkipMethod::class);

            if (($implement_all_methods && $skip_method !== null) || (!$implement_all_methods && $add_method === null)) {
                continue;
            }

            $method_data = new MethodData();

            $method_generator = $method_data->getGenerator();
            $method_generator->setFinal(true);
            $method_generator->setName($reflection_method->name);
            $method_generator->setVisibility('public');

            if ($reflection_method->getReturnType() !== null) {
                $type = (string)$reflection_method->getReturnType();

                if ($type && !$reflection_method->getReturnType()->isBuiltin()) {
                    if ($reflection_method->getReturnType()->allowsNull()) {
                        $type = str_replace('?', '?\\', $type);
                    } else {
                        $type = '\\' . $type;
                    }
                }

                $method_generator->setReturnType($type);
            }

            foreach ($reflection_method->getParameters() as $reflection_parameter) {
                $argument = new ParameterGenerator();
                $argument->setName($reflection_parameter->getName());
                $argument->setPosition($reflection_parameter->getPosition());

                if ($reflection_parameter->getType() !== null) {
                    $argument->setType($reflection_parameter->getType());
                }

                if ($reflection_parameter->isDefaultValueAvailable()) {
                    $argument->setDefaultValue($reflection_parameter->getDefaultValue());
                }

                $method_generator->setParameter($argument);

                $local_variable = new LocalVariable();
                $local_variable->name = $reflection_parameter->getName();
                $local_variable->init = false;

                $method_data->addLocalVariable($local_variable);
            }

            $method_annotations = [];

            foreach ($class_annotations as $class_annotation) {
                if ($class_annotation instanceof Before) {
                    foreach ($class_annotation->steps as $reader_annotation) {
                        if ($reader_annotation instanceof ContractMethodAnnotation) {
                            $method_annotations[] = $reader_annotation;
                        }
                    }
                }
            }

            $reader_annotations = Project::getAnnotationReader()->getMethodAnnotations($reflection_method);

            foreach ($reader_annotations as $reader_annotation) {
                if ($reader_annotation instanceof Collection) {
                    $collection_annotations = Project::collectMethodAnnotations($reader_annotation->class, $reader_annotation->method);

                    foreach ($collection_annotations as $collection_annotation) {
                        $method_annotations[] = $collection_annotation;
                    }
                } elseif ($reader_annotation instanceof ContractMethodAnnotation) {
                    $method_annotations[] = $reader_annotation;
                }
            }

            for ($i = count($class_annotations) - 1; $i >= 0; $i--) {
                $class_annotation = $class_annotations[$i];

                if ($class_annotation instanceof After) {
                    foreach ($class_annotation->steps as $reader_annotation) {
                        if ($reader_annotation instanceof ContractMethodAnnotation) {
                            $method_annotations[] = $reader_annotation;
                        }
                    }
                }
            }

            $set_annotations = [];
            $step_annotations = [];

            foreach ($method_annotations as $annotation) {
                if ($annotation instanceof Set) {
                    $set_annotations[] = $annotation;
                } else {
                    $step_annotations[] = $annotation;
                }
            }

            foreach ($method_annotations as $annotation) {
                $add_set_annotations = $this->onCreateMethodAnnotation($annotation, $reflection_class, $reflection_method, $base_class_data, $base_test_data, $class_data, $test_data, $method_data);

                $set_annotations = array_merge($set_annotations, $add_set_annotations);
            }

            $method_annotations = array_merge($set_annotations, $step_annotations);

            /** @var ContractMethodAnnotation $annotation */
            foreach ($method_annotations as $annotation) {
                $annotation->onAnalyze();
            }

            /** @var ContractMethodAnnotation $annotation */
            foreach ($method_annotations as $annotation) {
                $annotation->onBuild();
            }

            $set_annotations_to_add = [];
            $set_annotations_ids = [];

            /** @var Set $set_annotation */
            foreach ($set_annotations as $set_annotation) {
                if (!in_array($set_annotation->getId(), $set_annotations_ids)) {
                    $set_annotations_ids[] = $set_annotation->getId();
                    $set_annotations_to_add[] = $set_annotation;
                }
            }

            $method_data->setSets($set_annotations_to_add);
            $method_data->setSteps($step_annotations);

            if (count($method_data->getSteps()) > 0 || count($method_data->getSets()) > 0) {
                $method_data->generate();

                $base_class_generator->addMethodFromGenerator($method_generator);
            }
        }
    }

    private function generateContexts($contexts)
    {
        foreach ($contexts as $context) {
            $baseTestClass = new \Perfumer\Generator\StandardProject\Context\BaseTestFile($this->contextPrefix, $context, $this->generated_annotation_directory);
            $testClass = new TestFile($this->contextPrefix, $context, $this->generated_annotation_directory);

            $this->generated_tests_directory->addFile($baseTestClass);
            $this->tests_directory->addFile($testClass);
        }
    }

    private function onCreateMethodAnnotation(
        ContractMethodAnnotation $annotation,
        \ReflectionClass $reflection_class,
        \ReflectionMethod $reflection_method,
        BaseClassData $base_class_data,
        BaseTestData $base_test_data,
        ClassData $class_data,
        TestData $test_data,
        MethodData $method_data
    )
    {
        $annotation->setReflectionClass($reflection_class);
        $annotation->setReflectionMethod($reflection_method);
        $annotation->setBaseClassData($base_class_data);
        $annotation->setBaseTestData($base_test_data);
        $annotation->setClassData($class_data);
        $annotation->setTestData($test_data);
        $annotation->setMethodData($method_data);
        $annotation->onCreate();

        $add_annotations = [];

        if ($annotation instanceof ClassCall) {
            $context_annotations = Project::collectMethodAnnotations($annotation->_class, $annotation->_method);

            foreach ($context_annotations as $context_annotation) {
                if ($context_annotation instanceof Set) {
                    // Do not set annotations with different tags
                    if ($context_annotation->tags && !array_intersect($base_class_data->getTags(), $context_annotation->tags)) {
                        continue;
                    }

                    $context_annotation->setReflectionClass($reflection_class);
                    $context_annotation->setReflectionMethod($reflection_method);
                    $context_annotation->setBaseClassData($base_class_data);
                    $context_annotation->setBaseTestData($base_test_data);
                    $context_annotation->setClassData($class_data);
                    $context_annotation->setTestData($test_data);
                    $context_annotation->setMethodData($method_data);
                    $context_annotation->onCreate();

                    $add_annotations[] = $context_annotation;
                }
            }
        }

        return $add_annotations;
    }
}
