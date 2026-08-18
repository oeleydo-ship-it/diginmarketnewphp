<?php

namespace App\Rules;

use App\Support\RichText;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MinPlainText implements ValidationRule
{
    public function __construct(private int $min) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (RichText::plainLength(is_string($value) ? $value : '') < $this->min) {
            $fail('The '.$attribute.' field must be at least '.$this->min.' characters.');
        }
    }
}
