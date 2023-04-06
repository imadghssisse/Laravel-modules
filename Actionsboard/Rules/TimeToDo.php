<?php

namespace Modules\Actionsboard\Rules;

use Illuminate\Contracts\Validation\Rule;

class TimeToDo implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $data = explode('-', $value);
        $type = strtoupper($data[1]);
        if($type === "M" || $type === "H") {
          if(is_numeric($data[0])) {
            return true;
          }
        }
        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The validation error message.';
    }
}
