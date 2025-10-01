<?php

namespace App\Rules;

use App\Models\File;
use App\Services\UserService;
use Illuminate\Contracts\Validation\Rule;

class FileOwnedRule implements Rule
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

        $q = File::where('id', $value);

        if (!UserService::isAdmin()) {
            $q->where('user_id', UserService::id());
        }

        return $q->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute is not owned by current user.';
    }
}
