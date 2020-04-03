<?php declare(strict_types=1);

namespace WyriHaximus;

function validate_array(array $data, array $fields, string $exception = null): bool
{
    foreach ($fields as $field) {
        if (
            !isset($data[$field]) && // This is faster,
                                     // but it will also return false on fields which
                                     // value is null, so calling array_key_exists when that happens.
            !\array_key_exists($field, $data)
        ) {
            if ($exception === null) {
                return false;
            }

            throw new $exception($data, $field);
        }
    }

    return true;
}
