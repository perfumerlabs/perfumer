<?php

namespace Perfumerlabs\Perfumer\Data;

use Laminas\Code\Generator\DocBlock\Tag\ReturnTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\PropertyGenerator;

final class BaseClassData extends ClassData
{
    /**
     * @var array
     */
    private $shared_classes = [];

    /**
     * @var array
     */
    private $injections = [];

    /**
     * @var array
     */
    private $tags = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function getSharedClasses(): array
    {
        return $this->shared_classes;
    }

    public function setSharedClasses(array $shared_classes): void
    {
        $this->shared_classes = $shared_classes;
    }

    public function addSharedClass(string $shared_class): void
    {
        if ($shared_class[0] !== '\\') {
            $shared_class = '\\' . $shared_class;
        }

        if (in_array($shared_class, $this->shared_classes)) {
            return;
        }

        $this->shared_classes[] = $shared_class;
    }

    public function getInjections(): array
    {
        return $this->injections;
    }

    public function setInjections(array $injections): void
    {
        $this->injections = $injections;
    }

    public function addInjection(string $name, string $type): void
    {
        $built_in_types = ['int', 'string', 'bool', 'array', 'float'];

        if (!in_array($type, $built_in_types) && $type[0] !== '\\') {
            $type = '\\' . $type;
        }

        $this->injections[$name] = $type;
    }

    public function hasInjection(string $name): bool
    {
        return isset($this->injections[$name]);
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    public function addTag(string $name): void
    {
        if (!$this->hasTag($name)) {
            $this->tags[] = $name;
        }
    }

    public function hasTag(string $name): bool
    {
        return in_array($name, $this->tags);
    }

    public function generate(): string
    {
        $this->generateSharedClasses();
        $this->generateInjections();

        return parent::generate();
    }

    private function generateSharedClasses(): void
    {
        foreach ($this->shared_classes as $name => $class) {
            $name = str_replace('\\', '_', trim($class, '\\'));

            $doc_block = DocBlockGenerator::fromArray([
                'tags' => [
                    [
                        'name'        => 'var',
                        'description' => $class,
                    ]
                ],
            ]);

            $property = new PropertyGenerator();
            $property->setDocBlock($doc_block);
            $property->setVisibility('private');
            $property->setName('_shared_' . $name);

            $this->getGenerator()->addPropertyFromGenerator($property);

            $doc_block = DocBlockGenerator::fromArray([
                'tags' => [
                    new ReturnTag([
                        'datatype'  => $class,
                    ]),
                ],
            ]);

            $getter = new MethodGenerator();
            $getter->setDocBlock($doc_block);
            $getter->setVisibility('private');
            $getter->setName('get_' . $name);
            $getter->setReturnType($class);

            $getter->setBody('
                if ($this->_shared_' . $name . ' === null) {
                    $this->_shared_' . $name . ' = new ' . $class . '();
                }
                
                return $this->_shared_' . $name . ';'
            );

            $this->getGenerator()->addMethodFromGenerator($getter);
        }
    }

    private function generateInjections(): void
    {
        foreach ($this->injections as $name => $type) {
            $doc_block = DocBlockGenerator::fromArray([
                'tags' => [
                    [
                        'name'        => 'var',
                        'description' => $type,
                    ]
                ],
            ]);

            $property = new PropertyGenerator();
            $property->setDocBlock($doc_block);
            $property->setVisibility('private');
            $property->setName('_inject_' . $name);

            $this->getGenerator()->addPropertyFromGenerator($property);

            $constructor = $this->getGenerator()->getMethod('__construct');

            if (!$constructor) {
                $constructor = new MethodGenerator();
                $constructor->setVisibility('public');
                $constructor->setName('__construct');

                $this->getGenerator()->addMethodFromGenerator($constructor);
            }

            $body = $constructor->getBody() . PHP_EOL . '$this->_inject_' . $name . ' = $' . $name . ';';

            $constructor->setParameter(new ParameterGenerator($name, $type));
            $constructor->setBody($body);

            $doc_block = DocBlockGenerator::fromArray([
                'tags' => [
                    new ReturnTag([
                        'datatype'  => $type,
                    ]),
                ],
            ]);

            $getter = new MethodGenerator();
            $getter->setDocBlock($doc_block);
            $getter->setFinal(true);
            $getter->setVisibility('protected');
            $getter->setName('get' . str_replace('_', '', ucwords($name, '_')));
            $getter->setReturnType($type);
            $getter->setBody('return $this->_inject_' . $name . ';');

            $this->getGenerator()->addMethodFromGenerator($getter);
        }
    }
}
