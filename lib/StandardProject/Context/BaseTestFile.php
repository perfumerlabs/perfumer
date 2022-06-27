<?php

namespace Perfumer\Generator\StandardProject\Context;

use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Perfumer\Generator\Directory;
use Perfumer\Generator\LaminasClass;
use Perfumer\Generator\Project;
use Perfumerlabs\Perfumer\ContextAnnotation\AnnotationExtends;
use Perfumerlabs\Perfumer\ContextAnnotation\AnnotationProperty;
use Perfumerlabs\Perfumer\ContextAnnotation\AnnotationVariant;
use Perfumerlabs\Perfumer\ContextAnnotation\Test;
use Perfumerlabs\Perfumer\ContextAnnotation\UnitTest;
use Perfumerlabs\Perfumer\ContractAnnotation\SharedClassCall;

class BaseTestFile extends LaminasClass
{
    /**
     * @var \ReflectionClass
     */
    protected $reflectionClass;

    protected $contextPrefix;

    protected $contextClass;

    protected $skip = false;

    protected $generated_annotation_directory;

    public function __construct(string $contextPrefix, string $contextClass, Directory $generated_annotation_directory)
    {
        $this->contextPrefix = $contextPrefix;
        $this->contextClass = $contextClass;
        $this->reflectionClass = new \ReflectionClass($contextClass);
        $this->generated_annotation_directory = $generated_annotation_directory;

        $path = $this->buildPath();

        parent::__construct($contextClass, $path);
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
        $namespaceName = 'Generated\\Tests\\' . $this->reflectionClass->getNamespaceName();
        $name = $this->reflectionClass->getShortName() . 'Test';

        $output_name = str_replace('\\', '/', trim(str_replace('Generated\\Tests\\' . $this->contextPrefix, '', $namespaceName), '\\'));

        if ($output_name) {
            $output_name .= '/';
        }

        return $output_name . $name . '.php';
    }

    protected function buildGenerator(): void
    {
        $reflection_class = $this->reflectionClass;
        $class_generator = $this->getGenerator();

        $namespace = $this->reflectionClass->getNamespaceName();

        $class_generator->setNamespaceName('Generated\\Tests\\' . $namespace);
        $class_generator->setAbstract(true);
        $class_generator->setName($this->reflectionClass->getShortName() . 'Test');
        $class_generator->setExtendedClass('PHPUnit\\Framework\\TestCase');

        // If context is from another package
        if (strpos($class_generator->getNamespaceName(), 'Generated\\Tests\\' . $this->contextPrefix) !== 0) {
            $this->skip = true;
            return;
        }

        $class_annotation_variants = $this->collectContextClassAnnotationVariants($reflection_class);

        $data_providers = [];
        $test_methods = [];
        $assertions = [];
        $instantiations = [];

        foreach ($reflection_class->getMethods() as $reflection_method) {
            $test_annotation = Project::collectMethodAnnotation($reflection_class, $reflection_method, Test::class);

            if (!$test_annotation) {
                $test_annotation = Project::collectMethodAnnotation($reflection_class, $reflection_method, UnitTest::class);
            }

            if ($test_annotation) {
                $this->skip = true;

                $data_provider = new MethodGenerator();
                $data_provider->setAbstract(true);
                $data_provider->setVisibility('public');
                $data_provider->setName($reflection_method->name . 'DataProvider');

                $data_providers[] = $data_provider;

                $doc_block = DocBlockGenerator::fromArray([
                    'tags' => [
                        [
                            'name' => 'dataProvider',
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

                $arguments = array_map(function ($value) {
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
                $instantiation->setBody('return new \\' . ltrim($this->contextClass, '\\') . '();');

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
                    $annotationClass = new AnnotationClass($reflection_class, $reflection_method, $key, $annotation_variant['extends'], $annotation_variant['properties']);

                    $this->generated_annotation_directory->addFile($annotationClass);
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
    }

    private function collectContextClassAnnotationVariants(\ReflectionClass $reflection_class): array
    {
        $variants = [
            '_default' => [
                'extends' => SharedClassCall::class,
                'properties' => []
            ]
        ];

        $annotations = Project::collectContextClassAnnotations($reflection_class);

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

    private function collectContextMethodAnnotationVariants(\ReflectionMethod $reflection_method): array
    {
        $variants = [];

        $annotations = Project::collectContextMethodAnnotations(null, $reflection_method);

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
}
