<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class ClassCall extends Expression
{
    /**
     * @var string
     */
    public $_class;

    /**
     * @var string
     */
    public $_method;
}
