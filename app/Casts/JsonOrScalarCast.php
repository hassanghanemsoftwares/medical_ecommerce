<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class JsonOrScalarCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if (is_null($value)) {
            return null;
        }

        // Special case: if this is the about_us key, always decode JSON to array
        if ($model->key === 'about_us') {
            return json_decode($value, true) ?: [];
        }

        // Try to decode JSON for other keys
        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded))) {
            return $decoded;
        }

        return $value;  // scalar or string
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
