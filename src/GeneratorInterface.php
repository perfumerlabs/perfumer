<?php

namespace Perfumerlabs\Perfumer;

interface GeneratorInterface
{
    public function addModule(string $class, ?string $regex = null, array $exceptions = []);

    public function addContract(string $class, bool $has_default_context = false);

    public function addContext(string $class);

    public function generateAll();
}
