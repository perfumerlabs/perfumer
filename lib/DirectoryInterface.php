<?php

namespace Perfumer\Generator;

interface DirectoryInterface
{
    public function generate(): void;

    public function addFile(File $file): void;

    public function getFiles(): array;

    public function setFiles(array $files): void;
}
