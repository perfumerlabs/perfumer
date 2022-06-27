<?php

namespace Perfumer\Generator;

class Directory implements DirectoryInterface
{
    /**
     * @var File[]
     */
    private array $files = [];

    private string $name;

    private string $path;

    public function __construct(string $name, string $path)
    {
        $this->name = $name;
        $this->path = ltrim($path, '/');
    }

    public function generate(): void
    {
        foreach ($this->files as $file) {
            if ($file instanceof FileInterface) {
                $file->generate();
            }
        }
    }

    public function addFile(File $file): void
    {
        $file->setPath($this->getPath().'/'.$file->getPath());

        $this->files[$file->getName()] = $file;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * @param File[] $files
     */
    public function setFiles(array $files): void
    {
        foreach ($files as $file) {
            $this->addFile($file);
        }
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
