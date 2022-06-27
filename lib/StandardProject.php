<?php

namespace Perfumer\Generator;

use Perfumer\Generator\StandardProject\Collection\AnnotationClass;
use Perfumer\Generator\StandardProject\Context\BaseTestFile;
use Perfumer\Generator\StandardProject\Context\TestFile;
use Perfumerlabs\Perfumer\Bundle;
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
use Perfumerlabs\Perfumer\Data\AbstractData;
use Perfumerlabs\Perfumer\Data\BaseClassData;
use Perfumerlabs\Perfumer\Data\BaseTestData;
use Perfumerlabs\Perfumer\Data\ClassData;
use Perfumerlabs\Perfumer\Data\MethodData;
use Perfumerlabs\Perfumer\Data\TestData;
use Perfumerlabs\Perfumer\LocalVariable;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StandardProject extends Project
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
    private $generated_annotation_path;

    /**
     * @var string
     */
    private $generated_src_path;

    /**
     * @var string
     */
    private $generated_tests_path;

    /**
     * @var string
     */
    private $src_path;

    /**
     * @var string
     */
    private $tests_path;

    /**
     * @var Directory
     */
    private $generated_annotation_directory;

    /**
     * @var Directory
     */
    private $generated_src_directory;

    /**
     * @var Directory
     */
    private $generated_tests_directory;

    /**
     * @var Directory
     */
    private $src_directory;

    /**
     * @var Directory
     */
    private $tests_directory;

    /**
     * @var bool
     */
    private $prettify;

    public function __construct(string $root_dir, array $options = [])
    {
        parent::__construct();

        $this->setPath($root_dir);

        $resolver = new OptionsResolver();

        $resolver->setDefaults([
            'generated_annotation_path' => 'generated/annotation',
            'generated_src_path' => 'generated/src',
            'generated_tests_path' => 'generated/tests',
            'src_path' => 'src',
            'tests_path' => 'tests',
            'prettify' => true,
        ]);

        $resolver->setRequired('contract_prefix');
        $resolver->setRequired('class_prefix');
        $resolver->setRequired('context_prefix');

        $resolver->setAllowedTypes('contract_prefix', 'string');
        $resolver->setAllowedTypes('class_prefix', 'string');
        $resolver->setAllowedTypes('context_prefix', 'string');
        $resolver->setAllowedTypes('generated_annotation_path', 'string');
        $resolver->setAllowedTypes('generated_src_path', 'string');
        $resolver->setAllowedTypes('generated_tests_path', 'string');
        $resolver->setAllowedTypes('src_path', 'string');
        $resolver->setAllowedTypes('tests_path', 'string');
        $resolver->setAllowedTypes('prettify', 'bool');

        $options = $resolver->resolve($options);

        $this->contract_prefix = $options['contract_prefix'];
        $this->class_prefix = $options['class_prefix'];
        $this->context_prefix = $options['context_prefix'];
        $this->generated_annotation_path = $options['generated_annotation_path'];
        $this->generated_src_path = $options['generated_src_path'];
        $this->generated_tests_path = $options['generated_tests_path'];
        $this->src_path = $options['src_path'];
        $this->tests_path = $options['tests_path'];
        $this->prettify = $options['prettify'];

        $this->generated_annotation_directory = new Directory('generated_annotation_path', $this->generated_annotation_path);
        $this->generated_src_directory = new Directory('generated_src_path', $this->generated_src_path);
        $this->generated_tests_directory = new Directory('generated_tests_path', $this->generated_tests_path);
        $this->src_directory = new Directory('generated_src_path', $this->generated_src_path);
        $this->tests_directory = new Directory('generated_tests_path', $this->generated_tests_path);
    }

    private function collectModuleAnnotations()
    {
        foreach ($this->getModules() as &$module) {
            $reflection = new \ReflectionClass($module['class']);

            $module['annotations'] = self::getAnnotationReader()->getClassAnnotations($reflection);
        }
    }

    public function generate(): void
    {
        parent::generate();

        $this->generateContexts($this->getContexts());

        $this->generateCollections($this->getCollections());

        $this->collectModuleAnnotations();

        $bundle = new Bundle();

        foreach ($this->getContracts() as $contract) {
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

                foreach ($this->getModules() as $module) {
                    if ($module['regex'] === null || (!in_array($class, $module['exceptions']) && preg_match($module['regex'], $class))) {
                        foreach ($module['annotations'] as $module_annotation) {
                            if ($module_annotation instanceof ContractClassAnnotation) {
                                $class_annotations[] = $module_annotation;
                            }
                        }
                    }
                }

                $reader_annotations = $this->getAnnotationReader()->getClassAnnotations($reflection_class);

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

                        $reader_annotations = $this->getAnnotationReader()->getMethodAnnotations($reflection_method);

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

    private function generateCollections($collections)
    {
        foreach ($collections as $collection) {
            $reflection_class = new \ReflectionClass($collection);

            foreach ($reflection_class->getMethods() as $reflection_method) {
                $annotationClass = new AnnotationClass($reflection_class, $reflection_method);
                $this->generated_annotation_directory->addFile($annotationClass);
            }
        }
    }

    private function generateContexts($contexts)
    {
        foreach ($contexts as $context) {
            $baseTestClass = new BaseTestFile($this->context_prefix, $context, $this->generated_annotation_directory);
            $testClass = new TestFile($this->context_prefix, $context, $this->generated_annotation_directory);

            $this->generated_tests_directory->addFile($baseTestClass);
            $this->tests_directory->addFile($testClass);
        }
    }

    private function generateClass(AbstractData $data, ClassGenerator $generator, string $path, string $prefix, bool $overwrite): void
    {
        $output_name = str_replace('\\', '/', trim(str_replace($prefix . $this->class_prefix, '', $generator->getNamespaceName()), '\\'));

        if ($output_name) {
            $output_name .= '/';
        }

        @mkdir($this->getPath() . '/' . $path . '/' . $output_name, 0777, true);

        $output_name = $this->getPath() . '/' . $path . '/' . $output_name . $generator->getName() . '.php';

        if ($overwrite === false && is_file($output_name)) {
            return;
        }

        $code = '<?php' . PHP_EOL . PHP_EOL . $data->generate();

        file_put_contents($output_name, $code);
    }
}
