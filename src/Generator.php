<?php

namespace Perfumerlabs\Perfumer;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Perfumerlabs\Perfumer\ContextAnnotation\AnnotationExtends;
use Perfumerlabs\Perfumer\ContextAnnotation\AnnotationProperty;
use Perfumerlabs\Perfumer\ContextAnnotation\AnnotationVariant;
use Perfumerlabs\Perfumer\ContextAnnotation\Returns;
use Perfumerlabs\Perfumer\ContextAnnotation\Test;
use Perfumerlabs\Perfumer\ContractAnnotation\AddMethod;
use Perfumerlabs\Perfumer\ContractAnnotation\After;
use Perfumerlabs\Perfumer\ContractAnnotation\Before;
use Perfumerlabs\Perfumer\ContractAnnotation\ClassCall;
use Perfumerlabs\Perfumer\ContractAnnotation\ImplementNoMethods;
use Perfumerlabs\Perfumer\ContractAnnotation\Set;
use Perfumerlabs\Perfumer\ContractAnnotation\SharedClassCall;
use Perfumerlabs\Perfumer\ContractAnnotation\SkipMethod;
use Perfumerlabs\Perfumer\Data\AbstractData;
use Perfumerlabs\Perfumer\Data\BaseClassData;
use Perfumerlabs\Perfumer\Data\BaseTestData;
use Perfumerlabs\Perfumer\Data\ClassData;
use Perfumerlabs\Perfumer\Data\MethodData;
use Perfumerlabs\Perfumer\Data\TestData;
use Symfony\Component\ClassLoader\ClassMapGenerator;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\PropertyGenerator;

final class Generator implements GeneratorInterface
{
    /**
     * @var string
     */
    private $contract_prefix;

    /**
     * @var string
     */
    private $class_prefix;

    /**
     * @var string
     */
    private $context_prefix;

    /**
     * @var string
     */
    private $root_dir;

    /**
     * @var string
     */
    private $generated_annotation_path = 'generated/annotation';

    /**
     * @var string
     */
    private $generated_src_path = 'generated/src';

    /**
     * @var string
     */
    private $generated_tests_path = 'generated/tests';

    /**
     * @var string
     */
    private $src_path = 'src';

    /**
     * @var string
     */
    private $tests_path = 'tests';

    /**
     * @var array
     */
    private $modules = [];

    /**
     * @var array
     */
    private $collections = [];

    /**
     * @var array
     */
    private $contracts = [];

    /**
     * @var array
     */
    private $contexts = [];

    /**
     * @var bool
     */
    private $prettify = true;

    /**
     * @var AnnotationReader
     */
    private $reader;

    public function __construct(string $root_dir, array $options = [])
    {
        $this->reader = new AnnotationReader();

        /** @noinspection PhpDeprecationInspection */
        AnnotationRegistry::registerLoader('class_exists');

        $this->root_dir = $root_dir;

        if (isset($options['contract_prefix'])) {
            $this->contract_prefix = (string) $options['contract_prefix'];
        }

        if (isset($options['class_prefix'])) {
            $this->class_prefix = (string) $options['class_prefix'];
        }

        if (isset($options['context_prefix'])) {
            $this->context_prefix = (string) $options['context_prefix'];
        }

        if (isset($options['generated_annotation_path'])) {
            $this->generated_annotation_path = (string) $options['generated_annotation_path'];
        }

        if (isset($options['generated_src_path'])) {
            $this->generated_src_path = (string) $options['generated_src_path'];
        }

        if (isset($options['generated_tests_path'])) {
            $this->generated_tests_path = (string) $options['generated_tests_path'];
        }

        if (isset($options['src_path'])) {
            $this->src_path = (string) $options['src_path'];
        }

        if (isset($options['tests_path'])) {
            $this->tests_path = (string) $options['tests_path'];
        }

        if (isset($options['prettify'])) {
            $this->prettify = (bool) $options['prettify'];
        }
    }

    public function addModule(string $class, ?string $regex = null, array $exceptions = [])
    {
        $this->modules[] = [
            'class' => $class,
            'regex' => $regex,
            'exceptions' => $exceptions,
            'annotations' => []
        ];

        return $this;
    }

    public function addCollection(string $class)
    {
        if ($class[0] !== '\\') {
            $class = '\\' . $class;
        }

        if (!in_array($class, $this->collections)) {
            $this->collections[] = $class;
        }

        return $this;
    }

    public function addCollectionDirectory(string $path)
    {
        $classes = ClassMapGenerator::createMap($path);

        foreach (array_keys($classes) as $class) {
            $this->addCollection($class);
        }
    }

    public function addContract(string $class, bool $has_default_context = false)
    {
        $this->contracts[] = [
            'class' => $class,
            'has_default_context' => $has_default_context
        ];

        return $this;
    }

    public function addContractDirectory(string $path)
    {
        $classes = ClassMapGenerator::createMap($path);
        $classes = array_keys($classes);

        foreach ($classes as $class) {
            if (substr($class, -7, 7) === 'Context') {
                continue;
            }

            $has_default_context = in_array($class . 'Context', $classes);

            $this->addContract($class, $has_default_context);
        }
    }

    public function addContext(string $class)
    {
        if ($class[0] !== '\\') {
            $class = '\\' . $class;
        }

        if (!in_array($class, $this->contexts)) {
            $this->contexts[] = $class;
        }

        return $this;
    }

    public function addContextDirectory(string $path)
    {
        $classes = ClassMapGenerator::createMap($path);

        foreach (array_keys($classes) as $class) {
            $this->addContext($class);
        }
    }

    private function collectModuleAnnotations()
    {
        foreach ($this->modules as &$module) {
            $reflection = new \ReflectionClass($module['class']);

            $module['annotations'] = $this->reader->getClassAnnotations($reflection);
        }
    }

    public function generateAll()
    {
        $this->generateContexts($this->contexts);

        $this->generateCollections($this->collections);

        $this->collectModuleAnnotations();

        $bundle = new Bundle();

        foreach ($this->contracts as $contract) {
            $class = $contract['class'];

            try {
                $reflection_class = new \ReflectionClass($class);

                if ($contract['has_default_context']) {
                    $this->generateContexts([$class . 'Context']);
                }

                $implement_all_methods = true;

                $implement_no_methods = $this->collectClassAnnotation($reflection_class, ImplementNoMethods::class);

                if ($implement_no_methods !== null) {
                    $implement_all_methods = false;
                }

                $namespace = str_replace($this->contract_prefix, $this->class_prefix, $reflection_class->getNamespaceName());

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

                foreach ($this->modules as $module) {
                    if ($module['regex'] === null || (!in_array($class, $module['exceptions']) && preg_match($module['regex'], $class))) {
                        foreach ($module['annotations'] as $module_annotation) {
                            if ($module_annotation instanceof ContractClassAnnotation) {
                                $class_annotations[] = $module_annotation;
                            }
                        }
                    }
                }

                $reader_annotations = $this->reader->getClassAnnotations($reflection_class);

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
                    try {
                        $add_method = $this->collectMethodAnnotation($reflection_class, $reflection_method, AddMethod::class);
                        $skip_method = $this->collectMethodAnnotation($reflection_class, $reflection_method, SkipMethod::class);

                        if (($implement_all_methods && $skip_method !== null) || (!$implement_all_methods && $add_method === null)) {
                            continue;
                        }

                        $method_data = new MethodData();

                        $method_generator = $method_data->getGenerator();
                        $method_generator->setFinal(true);
                        $method_generator->setName($reflection_method->name);
                        $method_generator->setVisibility('public');

                        if ($reflection_method->getReturnType() !== null) {
                            $type = (string) $reflection_method->getReturnType();

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

                        $reader_annotations = $this->reader->getMethodAnnotations($reflection_method);

                        foreach ($reader_annotations as $reader_annotation) {
                            if ($reader_annotation instanceof Collection) {
                                $collection_annotations = $this->collectMethodAnnotations($reader_annotation->class, $reader_annotation->method);

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
                    } catch (\Throwable $exception) {
                        $message = sprintf('ERROR. %s->%s: %s' . PHP_EOL, $class, $reflection_method->getName(), $exception->getMessage());

                        exit($message);
                    }
                }

                $bundle->addBaseClassData($base_class_data);
                $bundle->addClassData($class_data);
                $bundle->addBaseTestData($base_test_data);
                $bundle->addTestData($test_data);
            } catch (\Throwable $exception) {
                $message = sprintf('ERROR. %s: %s' . PHP_EOL, $class, $exception->getMessage());

                exit($message);
            }
        }

        foreach ($bundle->getBaseClassData() as $base_class_data) {
            $this->generateClass($base_class_data, $base_class_data->getGenerator(), $this->generated_src_path, 'Generated\\', true);
        }

        foreach ($bundle->getClassData() as $class_data) {
            $this->generateClass($class_data, $class_data->getGenerator(), $this->src_path, '', false);
        }

        foreach ($bundle->getBaseTestData() as $base_test_data) {
            $this->generateClass($base_test_data, $base_test_data->getGenerator(), $this->generated_tests_path, 'Generated\\Tests\\', true);
        }

        foreach ($bundle->getTestData() as $test_data) {
            $this->generateClass($test_data, $test_data->getGenerator(), $this->tests_path, 'Tests\\', false);
        }

        if ($this->prettify) {
            shell_exec("vendor/bin/php-cs-fixer fix {$this->generated_annotation_path} --rules=@Symfony");
            shell_exec("vendor/bin/php-cs-fixer fix {$this->generated_src_path} --rules=@Symfony");
            shell_exec("vendor/bin/php-cs-fixer fix {$this->generated_tests_path} --rules=@Symfony");
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
            $context_annotations = $this->collectMethodAnnotations($annotation->_class, $annotation->_method);

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

    private function collectClassAnnotation($class, $annotation_name)
    {
        if (!$class instanceof \ReflectionClass) {
            $class = new \ReflectionClass($class);
        }

        $annotation = $this->reader->getClassAnnotation($class, $annotation_name);

        if ($annotation instanceof ContextAnnotation) {
            $annotation->onCreate();
        }

        return $annotation;
    }

    private function collectClassAnnotations($class)
    {
        $annotations = [];

        if (!$class instanceof \ReflectionClass) {
            $class = new \ReflectionClass($class);
        }

        $class_annotations = $this->reader->getClassAnnotations($class);

        foreach ($class_annotations as $class_annotation) {
            if ($class_annotation instanceof Annotation) {
                $annotations[] = $class_annotation;
            }
        }

        return $annotations;
    }

    private function collectContextClassAnnotations($class)
    {
        $context_annotations = [];

        $annotations = $this->collectClassAnnotations($class);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof ContextAnnotation) {
                $annotation->onCreate();

                $context_annotations[] = $annotation;
            }
        }

        return $context_annotations;
    }

    private function collectMethodAnnotations($class, $method)
    {
        $annotations = [];

        if (!$method instanceof \ReflectionMethod) {
            if (!$class instanceof \ReflectionClass) {
                $class = new \ReflectionClass($class);
            }

            foreach ($class->getMethods() as $class_method) {
                if ($class_method->getName() === $method) {
                    $method = $class_method;
                }
            }
        }

        $method_annotations = $this->reader->getMethodAnnotations($method);

        foreach ($method_annotations as $method_annotation) {
            if ($method_annotation instanceof Annotation) {
                $annotations[] = $method_annotation;
            }
        }

        return $annotations;
    }

    private function collectContextMethodAnnotations($class, $method)
    {
        $context_annotations = [];

        $annotations = $this->collectMethodAnnotations($class, $method);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof ContextAnnotation) {
                $annotation->onCreate();

                $context_annotations[] = $annotation;
            }
        }

        return $context_annotations;
    }

    private function collectMethodAnnotation($class, $method, $annotation_name)
    {
        if (!$method instanceof \ReflectionMethod) {
            if (!$class instanceof \ReflectionClass) {
                $class = new \ReflectionClass($class);
            }

            foreach ($class->getMethods() as $class_method) {
                if ($class_method->getName() === $method) {
                    $method = $class_method;
                }
            }
        }

        $annotation = $this->reader->getMethodAnnotation($method, $annotation_name);

        if ($annotation instanceof ContextAnnotation) {
            $annotation->onCreate();
        }

        return $annotation;
    }

    private function generateCollections($collections)
    {
        try {
            foreach ($collections as $collection) {
                $reflection_class = new \ReflectionClass($collection);

                foreach ($reflection_class->getMethods() as $reflection_method) {
                    $this->generateCollection($reflection_class, $reflection_method);
                }
            }
        } catch (\Exception $e) {
            exit($e->getMessage() . PHP_EOL);
        }
    }

    private function generateContexts($contexts)
    {
        try {
            foreach ($contexts as $context) {
                $reflection_class = new \ReflectionClass($context);

                $class_annotation_variants = $this->collectContextClassAnnotationVariants($reflection_class);

                $tests = false;

                $class_generator = new ClassGenerator();

                $namespace = $reflection_class->getNamespaceName();

                $class_generator->setNamespaceName('Generated\\Tests\\' . $namespace);
                $class_generator->setAbstract(true);
                $class_generator->setName($reflection_class->getShortName() . 'Test');
                $class_generator->setExtendedClass('PHPUnit\\Framework\\TestCase');

                $data_providers = [];
                $test_methods = [];
                $assertions = [];
                $instantiations = [];

                foreach ($reflection_class->getMethods() as $reflection_method) {
                    $test_annotation = $this->collectMethodAnnotation($reflection_class, $reflection_method, Test::class);

                    if ($test_annotation) {
                        $tests = true;

                        $data_provider = new MethodGenerator();
                        $data_provider->setAbstract(true);
                        $data_provider->setVisibility('public');
                        $data_provider->setName($reflection_method->name . 'DataProvider');

                        $data_providers[] = $data_provider;

                        $doc_block = DocBlockGenerator::fromArray([
                            'tags' => [
                                [
                                    'name'        => 'dataProvider',
                                    'description' => $reflection_method->name . 'DataProvider',
                                ]
                            ],
                        ]);

                        $test = new MethodGenerator();
                        $test->setDocBlock($doc_block);
                        $test->setFinal(true);
                        $test->setVisibility('public');
                        $test->setName('test' . ucfirst($reflection_method->name));

                        foreach ($reflection_method->getParameters() as $parameter) {
                            $argument = new ParameterGenerator();
                            $argument->setName($parameter->getName());
                            $argument->setPosition($parameter->getPosition());

                            if ($parameter->getType() !== null) {
                                $argument->setType($parameter->getType());
                            }

                            if ($parameter->isDefaultValueAvailable()) {
                                $argument->setDefaultValue($parameter->getDefaultValue());
                            }

                            $test->setParameter($argument);
                        }

                        $test->setParameter('expected');

                        $arguments = array_map(function($value) {
                            /** @var \ReflectionParameter $value */
                            return '$' . $value->getName();
                        }, $reflection_method->getParameters());

                        $body = '$_class_instance = $this->getClassInstance();' . PHP_EOL . PHP_EOL;
                        $body .= '$this->assertTest' . ucfirst($reflection_method->name) . '($expected, $_class_instance->' . $reflection_method->name . '(' . implode(', ', $arguments) . '));';

                        $test->setBody($body);

                        $test_methods[] = $test;

                        $instantiation = new MethodGenerator();
                        $instantiation->setVisibility('protected');
                        $instantiation->setName('getClassInstance');
                        $instantiation->setBody('return new \\' . ltrim($context, '\\') . '();');

                        $instantiations[] = $instantiation;

                        $assertion = new MethodGenerator();
                        $assertion->setVisibility('protected');
                        $assertion->setName('assertTest' . ucfirst($reflection_method->name));
                        $assertion->setParameter('expected');
                        $assertion->setParameter('result');
                        $assertion->setBody('$this->assertEquals($expected, $result);');

                        $assertions[] = $assertion;
                    }

                    $method_annotation_variants = $this->collectContextMethodAnnotationVariants($reflection_method);

                    $annotation_variants = array_merge($class_annotation_variants, $method_annotation_variants);

                    foreach ($annotation_variants as $key => $annotation_variant) {
                        if ($annotation_variant !== false) {
                            $this->generateAnnotation($reflection_class, $reflection_method, $key, $annotation_variant['extends'], $annotation_variant['properties']);
                        }
                    }
                }

                foreach ($data_providers as $data_provider) {
                    $class_generator->addMethodFromGenerator($data_provider);
                }

                foreach ($test_methods as $test_method) {
                    $class_generator->addMethodFromGenerator($test_method);
                }

                foreach ($instantiations as $instantiation) {
                    if (!$class_generator->hasMethod($instantiation->getName())) {
                        $class_generator->addMethodFromGenerator($instantiation);
                    }
                }

                foreach ($assertions as $assertion) {
                    if (!$class_generator->hasMethod($assertion->getName())) {
                        $class_generator->addMethodFromGenerator($assertion);
                    }
                }

                if ($tests) {
                    $this->generateBaseContextTest($class_generator);
                    $this->generateContextTest($class_generator);
                }
            }
        } catch (\Exception $e) {
            exit($e->getMessage() . PHP_EOL);
        }
    }

    private function collectContextClassAnnotationVariants(\ReflectionClass $reflection_class)
    {
        $variants = [
            '_default' => [
                'extends' => SharedClassCall::class,
                'properties' => []
            ]
        ];

        $annotations = $this->collectContextClassAnnotations($reflection_class);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof AnnotationVariant) {
                $variant_name = $annotation->name ?: '_default';

                if ($annotation->skip === true) {
                    $variants[$variant_name] = false;
                } else {
                    if (!isset($variants[$variant_name])) {
                        $variants[$variant_name] = [
                            'extends' => SharedClassCall::class,
                            'properties' => []
                        ];
                    }

                    foreach ($annotation->options as $variant_option) {
                        if ($variant_option instanceof AnnotationExtends) {
                            $variants[$variant_name]['extends'] = $variant_option->class;
                        }

                        if ($variant_option instanceof AnnotationProperty) {
                            $variants[$variant_name]['properties'][] = $variant_option;
                        }
                    }
                }
            }

            if ($annotation instanceof AnnotationExtends && $variants['_default'] !== false) {
                $variants['_default']['extends'] = $annotation->class;
            }

            if ($annotation instanceof AnnotationProperty && $variants['_default'] !== false) {
                $variants['_default']['properties'][] = $annotation;
            }
        }

        return $variants;
    }

    private function collectContextMethodAnnotationVariants(\ReflectionMethod $reflection_method)
    {
        $variants = [];

        $annotations = $this->collectContextMethodAnnotations(null, $reflection_method);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof AnnotationVariant) {
                $variant_name = $annotation->name ?: '_default';

                if ($annotation->skip === true) {
                    $variants[$variant_name] = false;
                } else {
                    if (!isset($variants[$variant_name])) {
                        $variants[$variant_name] = [
                            'extends' => SharedClassCall::class,
                            'properties' => []
                        ];
                    }

                    foreach ($annotation->options as $variant_option) {
                        if ($variant_option instanceof AnnotationExtends) {
                            $variants[$variant_name]['extends'] = $variant_option->class;
                        }

                        if ($variant_option instanceof AnnotationProperty) {
                            $variants[$variant_name]['properties'][] = $variant_option;
                        }
                    }
                }
            }

            if (($annotation instanceof AnnotationExtends || $annotation instanceof AnnotationProperty) && !isset($variants['_default'])) {
                $variants['_default'] = [
                    'extends' => SharedClassCall::class,
                    'properties' => []
                ];
            }

            if ($annotation instanceof AnnotationExtends && $variants['_default'] !== false) {
                $variants['_default']['extends'] = $annotation->class;
            }

            if ($annotation instanceof AnnotationProperty && $variants['_default'] !== false) {
                $variants['_default']['properties'][] = $annotation;
            }
        }

        return $variants;
    }

    private function generateCollection(\ReflectionClass $reflection_class, \ReflectionMethod $reflection_method)
    {
        $namespace = str_replace('\\', '/', $reflection_class->getNamespaceName()) . '/' . $reflection_class->getShortName();

        $class_name = ucfirst($reflection_method->getName());

        @mkdir($this->root_dir . '/' . $this->generated_annotation_path . '/' . $namespace, 0777, true);

        $output_name = $this->root_dir . '/' . $this->generated_annotation_path . '/' . $namespace . '/' .$class_name . '.php';

        $doc_block = DocBlockGenerator::fromArray([
            'tags' => [
                [
                    'name' => 'Annotation',
                ],
                [
                    'name' => 'Target({"CLASS", "METHOD", "ANNOTATION"})'
                ]
            ],
        ]);

        $class_generator = new ClassGenerator();
        $class_generator->setDocBlock($doc_block);
        $class_generator->setNamespaceName('Generated\\Annotation\\' . $reflection_class->getName());
        $class_generator->setName($class_name);
        $class_generator->setExtendedClass('\\Perfumerlabs\\Perfumer\\Collection');

        $doc_block = DocBlockGenerator::fromArray([
            'tags' => [
                [
                    'name'        => 'var',
                    'description' => 'string',
                ]
            ],
        ]);

        $property = new PropertyGenerator();
        $property->setDocBlock($doc_block);
        $property->setVisibility('public');
        $property->setName('class');
        $property->setDefaultValue('\\' . $reflection_class->getName());

        $class_generator->addPropertyFromGenerator($property);

        $property = new PropertyGenerator();
        $property->setDocBlock($doc_block);
        $property->setVisibility('public');
        $property->setName('method');
        $property->setDefaultValue($reflection_method->getName());

        $class_generator->addPropertyFromGenerator($property);

        $code = '<?php' . PHP_EOL . PHP_EOL . $class_generator->generate();

        file_put_contents($output_name, $code);
    }

    private function generateAnnotation(\ReflectionClass $reflection_class, \ReflectionMethod $reflection_method, $name, $extends, $properties = [])
    {
        $name_suffix = $name !== '_default' ? ucfirst($name) : '';

        $namespace = str_replace('\\', '/', $reflection_class->getNamespaceName()) . '/' . $reflection_class->getShortName();

        $class_name = ucfirst($reflection_method->getName()) . $name_suffix;

        @mkdir($this->root_dir . '/' . $this->generated_annotation_path . '/' . $namespace, 0777, true);

        $output_name = $this->root_dir . '/' . $this->generated_annotation_path . '/' . $namespace . '/' .$class_name . '.php';

        $doc_block = DocBlockGenerator::fromArray([
            'tags' => [
                [
                    'name' => 'Annotation',
                ],
                [
                    'name' => 'Target({"CLASS", "METHOD", "ANNOTATION"})'
                ]
            ],
        ]);

        $class_generator = new ClassGenerator();
        $class_generator->setDocBlock($doc_block);
        $class_generator->setNamespaceName('Generated\\Annotation\\' . $reflection_class->getName());
        $class_generator->setName($class_name);

        $returns_annotation = $this->collectMethodAnnotation($reflection_class, $reflection_method, Returns::class);

        if ($extends[0] !== '\\') {
            $extends = '\\' . $extends;
        }

        $class_generator->setExtendedClass($extends);

        foreach ($reflection_method->getParameters() as $reflection_parameter) {
            $doc_block = DocBlockGenerator::fromArray([
                'tags' => [
                    [
                        'name'        => 'var',
                        'description' => 'string',
                    ]
                ],
            ]);

            $property = new PropertyGenerator();
            $property->setDocBlock($doc_block);
            $property->setVisibility('public');
            $property->setName('in_' . $reflection_parameter->getName());

            $class_generator->addPropertyFromGenerator($property);

            $doc_block = DocBlockGenerator::fromArray([
                'tags' => [
                    [
                        'name'        => 'var',
                        'description' => 'string',
                    ]
                ],
            ]);

            $property = new PropertyGenerator();
            $property->setDocBlock($doc_block);
            $property->setVisibility('public');
            $property->setName($reflection_parameter->getName());

            $class_generator->addPropertyFromGenerator($property);
        }

        if ($returns_annotation) {
            $doc_block = DocBlockGenerator::fromArray([
                'tags' => [
                    [
                        'name'        => 'var',
                        'description' => 'string',
                    ]
                ],
            ]);

            foreach ($returns_annotation->names as $name) {
                $property = new PropertyGenerator();
                $property->setDocBlock($doc_block);
                $property->setVisibility('public');
                $property->setName('out_' . $name);

                $class_generator->addPropertyFromGenerator($property);
            }
        } else {
            $doc_block = DocBlockGenerator::fromArray([
                'tags' => [
                    [
                        'name'        => 'var',
                        'description' => 'mixed',
                    ]
                ],
            ]);

            $property = new PropertyGenerator();
            $property->setDocBlock($doc_block);
            $property->setVisibility('public');
            $property->setName('out');

            $class_generator->addPropertyFromGenerator($property);
        }

        // onCreate()
        $method_generator = new MethodGenerator();
        $method_generator->setName('onCreate');
        $method_generator->setVisibility('public');
        $method_generator->setReturnType('void');

        $body = '$this->_class = \'' . str_replace('\\', '\\\\', $reflection_class->getNamespaceName()) . '\\\\' . $reflection_class->getShortName() . '\';
        $this->_method = \'' . $reflection_method->getName() . '\';' . PHP_EOL;

        if ($returns_annotation) {
            if ($returns_annotation->assoc) {
                $body .= '$this->_return = [];' . PHP_EOL . PHP_EOL;

                foreach ($returns_annotation->names as $name) {
                    $body .= 'if(is_string($this->out_' . $name . ')) {' . PHP_EOL;
                    $body .= '$this->_return[$this->out_' . $name . '] = true;' . PHP_EOL;
                    $body .= '}' . PHP_EOL . PHP_EOL;
                }
            } else {
                $body .= '$this->_return = [];' . PHP_EOL . PHP_EOL;

                foreach ($returns_annotation->names as $name) {
                    $body .= 'if(is_string($this->out_' . $name . ')) {' . PHP_EOL;
                    $body .= '$this->_return[] = $this->out_' . $name . ';' . PHP_EOL;
                    $body .= '}' . PHP_EOL . PHP_EOL;
                }
            }
        } else {
            $body .= '$this->_return = $this->out;';
        }

        /** @var AnnotationProperty $property */
        foreach ($properties as $property) {
            $body .= sprintf('$this->%s = \'%s\';', $property->name, $property->value) . PHP_EOL;
        }

        $body .= PHP_EOL . PHP_EOL . 'parent::onCreate();';

        $method_generator->setBody($body);

        $class_generator->addMethodFromGenerator($method_generator);

        // onAnalyze()
        $method_generator = new MethodGenerator();
        $method_generator->setName('onAnalyze');
        $method_generator->setVisibility('public');
        $method_generator->setReturnType('void');

        $body = '';

        foreach ($reflection_method->getParameters() as $reflection_parameter) {
            $name = $reflection_parameter->getName();

            $body .= sprintf('$in_%s = $this->in_%s ?: $this->%s;', $name, $name, $name) . PHP_EOL . PHP_EOL;

            if ($reflection_parameter->isOptional()) {
                $body .= sprintf('if (!$in_%s && $this->getMethodData()->hasLocalVariable(\'%s\')) {', $name, $name) . PHP_EOL;
            } else {
                $body .= sprintf('if (!$in_%s) {', $name) . PHP_EOL;
            }

            $body .= sprintf('$in_%s = \'%s\';', $name, $name) . PHP_EOL;
            $body .= '}' . PHP_EOL . PHP_EOL;
        }

        $body .= '$this->_arguments = [];' . PHP_EOL . PHP_EOL;

        foreach ($reflection_method->getParameters() as $reflection_parameter) {
            $body .= 'if(is_string($in_' . $reflection_parameter->getName() . ')) {' . PHP_EOL;
            $body .= '$this->_arguments[] = $in_' . $reflection_parameter->getName() . ';' . PHP_EOL;
            $body .= '}' . PHP_EOL . PHP_EOL;
        }

        $body .= PHP_EOL . PHP_EOL . 'parent::onAnalyze();';

        $method_generator->setBody($body);

        $class_generator->addMethodFromGenerator($method_generator);

        $code = '<?php' . PHP_EOL . PHP_EOL . $class_generator->generate();

        file_put_contents($output_name, $code);
    }

    private function generateClass(AbstractData $data, ClassGenerator $generator, string $path, string $prefix, bool $overwrite): void
    {
        $output_name = str_replace('\\', '/', trim(str_replace($prefix . $this->class_prefix, '', $generator->getNamespaceName()), '\\'));

        if ($output_name) {
            $output_name .= '/';
        }

        @mkdir($this->root_dir . '/' . $path . '/' . $output_name, 0777, true);

        $output_name = $this->root_dir . '/' . $path . '/' . $output_name . $generator->getName() . '.php';

        if ($overwrite === false && is_file($output_name)) {
            return;
        }

        $code = '<?php' . PHP_EOL . PHP_EOL . $data->generate();

        file_put_contents($output_name, $code);
    }

    private function generateBaseContextTest(ClassGenerator $class_generator)
    {
        // If context is from another package
        if (strpos($class_generator->getNamespaceName(), 'Generated\\Tests\\' . $this->context_prefix) !== 0) {
            return;
        }

        $output_name = str_replace('\\', '/', trim(str_replace('Generated\\Tests\\' . $this->context_prefix, '', $class_generator->getNamespaceName()), '\\'));

        if ($output_name) {
            $output_name .= '/';
        }

        @mkdir($this->root_dir . '/' . $this->generated_tests_path . '/' . $output_name, 0777, true);

        $output_name = $this->root_dir . '/' . $this->generated_tests_path . '/' . $output_name . $class_generator->getName() . '.php';

        $code = '<?php' . PHP_EOL . PHP_EOL . $class_generator->generate();

        file_put_contents($output_name, $code);
    }

    private function generateContextTest(ClassGenerator $class_generator)
    {
        // If context is from another package
        if (strpos($class_generator->getNamespaceName(), 'Generated\\Tests\\' . $this->context_prefix) !== 0) {
            return;
        }

        $output_name = str_replace('\\', '/', trim(str_replace('Generated\\Tests\\' . $this->context_prefix, '', $class_generator->getNamespaceName()), '\\'));

        if ($output_name) {
            $output_name .= '/';
        }

        @mkdir($this->root_dir . '/' . $this->tests_path . '/' . $output_name, 0777, true);

        $output_name = $this->root_dir . '/' . $this->tests_path . '/' . $output_name . $class_generator->getName() . '.php';

        if (is_file($output_name)) {
            return;
        }

        $class = new ClassGenerator();
        $class->setNamespaceName(str_replace('Generated\\', '', $class_generator->getNamespaceName()));
        $class->setName($class_generator->getName());
        $class->setExtendedClass($class_generator->getNamespaceName() . '\\' . $class_generator->getName());

        foreach ($class_generator->getMethods() as $method_generator) {
            if ($method_generator->isAbstract()) {
                $method = new MethodGenerator();
                $method->setName($method_generator->getName());
                $method->setParameters($method_generator->getParameters());
                $method->setVisibility($method_generator->getVisibility());
                $method->setReturnType($method_generator->getReturnType());
                $method->setBody('throw new \Exception(\'Method "' . $method->getName() . '" is not implemented yet.\');');

                $class->addMethodFromGenerator($method);
            }
        }

        $code = '<?php' . PHP_EOL . PHP_EOL . $class->generate();

        file_put_contents($output_name, $code);
    }
}
