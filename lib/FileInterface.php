<?php

namespace Perfumer\Generator;

interface FileInterface
{
    public function generate(): void;

    public function build(): string|bool;
}
