<?php

namespace Perfumerlabs\Perfumer\Data;

use Zend\Code\Generator\ClassGenerator;

class TestData extends AbstractData
{
    /**
     * @var ClassGenerator
     */
    private $generator;

    public function __construct()
    {
        $this->generator = new ClassGenerator();
    }

    public function getGenerator(): ClassGenerator
    {
        return $this->generator;
    }

    public function setGenerator(ClassGenerator $generator): void
    {
        $this->generator = $generator;
    }

    public function generate(): string
    {
        return $this->generator->generate();
    }
}
