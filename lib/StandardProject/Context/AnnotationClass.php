<?php

namespace Perfumer\Generator\StandardProject\Context;

use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Perfumer\Generator\LaminasClass;
use Perfumer\Generator\Project;
use Perfumerlabs\Perfumer\ContextAnnotation\AnnotationProperty;
use Perfumerlabs\Perfumer\ContextAnnotation\Returns;

class AnnotationClass extends LaminasClass
{
    /**
     * @var \ReflectionClass
     */
    private $reflection_class;

    /**
     * @var \ReflectionMethod
     */
    private $reflection_method;

    private $name;

    private $extends;

    private $properties;

    public function __construct(\ReflectionClass $reflection_class, \ReflectionMethod $reflection_method, $name, $extends, $properties = [])
    {
        $this->reflection_class = $reflection_class;
        $this->reflection_method = $reflection_method;
        $this->name = $name;
        $this->extends = $extends;
        $this->properties = $properties;

        $name_suffix = $name !== '_default' ? ucfirst($name) : '';

        $namespace = str_replace('\\', '/', $reflection_class->getNamespaceName()) . '/' . $reflection_class->getShortName();

        $class_name = ucfirst($reflection_method->getName()) . $name_suffix;

        $output_name = $namespace . '/' .$class_name . '.php';

        parent::__construct($output_name, $output_name);
    }

    public function build(): string|bool
    {
        $reflection_class = $this->reflection_class;
        $reflection_method = $this->reflection_method;

        $name_suffix = $this->name !== '_default' ? ucfirst($this->name) : '';

        $class_name = ucfirst($reflection_method->getName()) . $name_suffix;

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

        $class_generator = $this->getGenerator();
        $class_generator->setDocBlock($doc_block);
        $class_generator->setNamespaceName('Generated\\Annotation\\' . $reflection_class->getName());
        $class_generator->setName($class_name);

        $returns_annotation = Project::collectMethodAnnotation($reflection_class, $reflection_method, Returns::class);

        if ($this->extends[0] !== '\\') {
            $this->extends = '\\' . $this->extends;
        }

        $class_generator->setExtendedClass($this->extends);

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
        foreach ($this->properties as $property) {
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

        return parent::build();
    }
}
