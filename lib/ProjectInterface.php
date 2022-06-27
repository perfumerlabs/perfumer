<?php

namespace Perfumer\Generator;

interface ProjectInterface
{
    public function generate(): void;

    public function addDirectory(Directory $directory): void;

    public function getDirectories(): array;

    public function setDirectories(array $directories): void;

    public function getPath(): ?string;

    public function setPath(string $path): void;
}
