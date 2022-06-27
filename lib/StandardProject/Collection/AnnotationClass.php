<?php

namespace Perfumer\Generator\StandardProject\Collection;

use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Perfumer\Generator\LaminasClass;

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

    public function __construct(\ReflectionClass $reflection_class, \ReflectionMethod $reflection_method)
    {
        $this->reflection_class = $reflection_class;
        $this->reflection_method = $reflection_method;

        $namespace = str_replace('\\', '/', $reflection_class->getNamespaceName()) . '/' . $reflection_class->getShortName();

        $class_name = ucfirst($reflection_method->getName());

        $output_name = $namespace . '/' . $class_name . '.php';

        parent::__construct($output_name, $output_name);
    }

    public function build(): string|bool
    {
        $class_name = ucfirst($this->reflection_method->getName());

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
        $class_generator->setNamespaceName('Generated\\Annotation\\' . $this->reflection_class->getName());
        $class_generator->setName($class_name);
        $class_generator->setExtendedClass('\\Perfumerlabs\\Perfumer\\Collection');

        $doc_block = DocBlockGenerator::fromArray([
            'tags' => [
                [
                    'name' => 'var',
                    'description' => 'string',
                ]
            ],
        ]);

        $property = new PropertyGenerator();
        $property->setDocBlock($doc_block);
        $property->setVisibility('public');
        $property->setName('class');
        $property->setDefaultValue('\\' . $this->reflection_class->getName());

        $class_generator->addPropertyFromGenerator($property);

        $property = new PropertyGenerator();
        $property->setDocBlock($doc_block);
        $property->setVisibility('public');
        $property->setName('method');
        $property->setDefaultValue($this->reflection_method->getName());

        $class_generator->addPropertyFromGenerator($property);

        return parent::build();
    }
}
