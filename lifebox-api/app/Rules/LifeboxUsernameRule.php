<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class LifeboxUsernameRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return filter_var(
            sprintf('%s@%s', $value, config('app.subdomain')),
            FILTER_VALIDATE_EMAIL
        ) !== false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'This Lifebox :attribute is invalid. Only single-word common and proper nouns are allowed.';
    }
}
