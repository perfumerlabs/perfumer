<?php

namespace Perfumer\Generator;

class File implements FileInterface
{
    private string $name;

    private string $path;

    public function __construct(string $name, string $path)
    {
        $this->name = $name;
        $this->path = ltrim($path, '/');
    }

    public function generate(): void
    {
        $content = $this->build();

        if (is_string($content)) {
            $path = explode('/', $this->path);
            unset($path[count($path) - 1]);
            $path = implode('/', $path);

            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }

            file_put_contents($this->path, $content);
        }
    }

    public function build(): string|bool
    {
        return false;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }
}
