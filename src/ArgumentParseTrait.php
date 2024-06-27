<?php

namespace Perfumerlabs\Perfumer;

trait ArgumentParseTrait
{
    public function parseArgument($values, array $args, array $customKeys = []): array
    {
        // это вызов через атрибут
        if ($args) {
            return $args;
        }

        // вызов через аннотацию
        if (!is_array($values)) {
            return [];
        }

        foreach ($customKeys as $key) {
            if (isset($values[$key])) {
                $this->$key = $values[$key];
                unset($values[$key]);
            }
        }

        return $values;
    }
}
