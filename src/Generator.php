<?php

namespace Perfumerlabs\Perfumer;

use Perfumer\Generator\StandardProject;

/**
 * @deprecated
 */
class Generator extends StandardProject implements GeneratorInterface
{
    /**
     * @deprecated
     */
    public function generateAll()
    {
        $this->generate();
    }
}
