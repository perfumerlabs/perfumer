<?php

namespace Perfumer\Generator;

use Perfumerlabs\Perfumer\Collection;
use Perfumerlabs\Perfumer\ContractAnnotation;
use Perfumerlabs\Perfumer\ContractAnnotation\After;
use Perfumerlabs\Perfumer\ContractAnnotation\Before;
use Perfumerlabs\Perfumer\ContractClassAnnotation;
use Perfumerlabs\Perfumer\ContractMethodAnnotation;

class ContractMethod
{
    public function collectAnnotations(
        \ReflectionClass $reflectionClass,
        \ReflectionMethod $reflectionMethod,
        array $moduleAnnotations
    ): array
    {
        $class = $reflectionClass->getName();

        /** @var ContractAnnotation[] $classAnnotations */
        $classAnnotations = [];

        foreach ($moduleAnnotations as $module) {
            if ($module['regex'] === null || (!in_array($class, $module['exceptions']) && preg_match($module['regex'], $class))) {
                foreach ($module['annotations'] as $moduleAnnotation) {
                    if ($moduleAnnotation instanceof ContractClassAnnotation) {
                        $classAnnotations[] = $moduleAnnotation;
                    }
                }
            }
        }

        $readerAnnotations = Project::getAnnotationReader()->getClassAnnotations($reflectionClass);

        foreach ($readerAnnotations as $readerAnnotation) {
            if ($readerAnnotation instanceof ContractClassAnnotation) {
                $classAnnotations[] = $readerAnnotation;
            }
        }

        $methodAnnotations = [];

        foreach ($classAnnotations as $classAnnotation) {
            if ($classAnnotation instanceof Before) {
                foreach ($classAnnotation->steps as $readerAnnotation) {
                    if ($readerAnnotation instanceof ContractMethodAnnotation) {
                        $methodAnnotations[] = $readerAnnotation;
                    }
                }
            }
        }

        $readerAnnotations = Project::getAnnotationReader()->getMethodAnnotations($reflectionMethod);

        foreach ($readerAnnotations as $readerAnnotation) {
            if ($readerAnnotation instanceof Collection) {
                $collectionAnnotations = Project::collectMethodAnnotations($readerAnnotation->class, $readerAnnotation->method);

                foreach ($collectionAnnotations as $collectionAnnotation) {
                    $methodAnnotations[] = $collectionAnnotation;
                }
            } elseif ($readerAnnotation instanceof ContractMethodAnnotation) {
                $methodAnnotations[] = $readerAnnotation;
            }
        }

        for ($i = count($classAnnotations) - 1; $i >= 0; $i--) {
            $classAnnotation = $classAnnotations[$i];

            if ($classAnnotation instanceof After) {
                foreach ($classAnnotation->steps as $readerAnnotation) {
                    if ($readerAnnotation instanceof ContractMethodAnnotation) {
                        $methodAnnotations[] = $readerAnnotation;
                    }
                }
            }
        }

        return $methodAnnotations;
    }
}
