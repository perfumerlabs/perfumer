<?php

namespace Perfumer\Generator;

use Laminas\Code\Generator\ClassGenerator;

class LaminasClass extends File implements ClassInterface
{
    /**
     * @var ClassGenerator
     */
    private $generator;

    public function __construct(string $name, string $path)
    {
        parent::__construct($name, $path);

        $this->generator = new ClassGenerator();
    }

    public function build(): string|bool
    {
        return '<?php' . PHP_EOL . PHP_EOL . $this->generator->generate();
    }

    /**
     * @return ClassGenerator
     */
    protected function getGenerator(): ClassGenerator
    {
        return $this->generator;
    }

    /**
     * @param ClassGenerator $generator
     */
    public function setGenerator(ClassGenerator $generator): void
    {
        $this->generator = $generator;
    }
}
