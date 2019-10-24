<?php

namespace Perfumerlabs\Perfumer;

class LocalVariable
{
    const VIEW_REGULAR = 1;
    const VIEW_CLOSURE = 2;

    public $name;

    public $init = true;

    public $is_validatable = false;

    public $valid_state = true;

    public $view = self::VIEW_REGULAR;

    public function render(): string
    {
        if ($this->view === self::VIEW_REGULAR) {
            return '$' . $this->name;
        }

        if ($this->view === self::VIEW_CLOSURE) {
            return '$' . $this->name . '()';
        }

        return '';
    }
}
