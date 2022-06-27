<?php

namespace Perfumer\Generator;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Perfumerlabs\Perfumer\Annotation;
use Perfumerlabs\Perfumer\ContextAnnotation;
use Symfony\Component\ClassLoader\ClassMapGenerator;

class Project implements ProjectInterface
{
    /**
     * @var Directory[]
     */
    private array $directories = [];

    private string $path;

    private array $modules = [];

    private array $collections = [];

    private array $contracts = [];

    private array $contexts = [];

    /**
     * @var AnnotationReader
     */
    private static $annotationReader;

    public function __construct()
    {
    }

    public static function getAnnotationReader()
    {
        if (!self::$annotationReader) {
            self::$annotationReader = new AnnotationReader();

            /** @noinspection PhpDeprecationInspection */
            AnnotationRegistry::registerLoader('class_exists');
        }

        return self::$annotationReader;
    }

    public function generate(): void
    {
        foreach ($this->directories as $directory) {
            if ($directory instanceof DirectoryInterface) {
                $directory->generate();
            }
        }
    }

    public function addDirectory(Directory $directory): void
    {
        $directory->setPath($this->getPath().'/'.$directory->getPath());

        $this->directories[$directory->getName()] = $directory;
    }

    public function getDirectories(): array
    {
        return $this->directories;
    }

    /**
     * @param Directory[] $directories
     */
    public function setDirectories(array $directories): void
    {
        foreach ($directories as $directory) {
            $this->addDirectory($directory);
        }
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
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

    /**
     * @return array
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * @return array
     */
    public function getCollections(): array
    {
        return $this->collections;
    }

    /**
     * @return array
     */
    public function getContracts(): array
    {
        return $this->contracts;
    }

    /**
     * @return array
     */
    public function getContexts(): array
    {
        return $this->contexts;
    }

    public static function collectClassAnnotations($class)
    {
        $annotations = [];

        if (!$class instanceof \ReflectionClass) {
            $class = new \ReflectionClass($class);
        }

        $class_annotations = self::getAnnotationReader()->getClassAnnotations($class);

        foreach ($class_annotations as $class_annotation) {
            if ($class_annotation instanceof Annotation) {
                $annotations[] = $class_annotation;
            }
        }

        return $annotations;
    }

    public static function collectClassAnnotation($class, $annotation_name)
    {
        if (!$class instanceof \ReflectionClass) {
            $class = new \ReflectionClass($class);
        }

        $annotation = self::getAnnotationReader()->getClassAnnotation($class, $annotation_name);

        if ($annotation instanceof ContextAnnotation) {
            $annotation->onCreate();
        }

        return $annotation;
    }

    public static function collectContextClassAnnotations($class)
    {
        $context_annotations = [];

        $annotations = self::collectClassAnnotations($class);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof ContextAnnotation) {
                $annotation->onCreate();

                $context_annotations[] = $annotation;
            }
        }

        return $context_annotations;
    }

    public static function collectMethodAnnotations($class, $method)
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

        $method_annotations = self::getAnnotationReader()->getMethodAnnotations($method);

        foreach ($method_annotations as $method_annotation) {
            if ($method_annotation instanceof Annotation) {
                $annotations[] = $method_annotation;
            }
        }

        return $annotations;
    }

    public static function collectContextMethodAnnotations($class, $method)
    {
        $context_annotations = [];

        $annotations = self::collectMethodAnnotations($class, $method);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof ContextAnnotation) {
                $annotation->onCreate();

                $context_annotations[] = $annotation;
            }
        }

        return $context_annotations;
    }

    public static function collectMethodAnnotation($class, $method, $annotation_name)
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

        $annotation = self::getAnnotationReader()->getMethodAnnotation($method, $annotation_name);

        if ($annotation instanceof ContextAnnotation) {
            $annotation->onCreate();
        }

        return $annotation;
    }
}
