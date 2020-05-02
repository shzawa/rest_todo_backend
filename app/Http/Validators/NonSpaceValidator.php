<?php

namespace App\Http\Validators;

use Illuminate\Validation\Validator;

class NonSpaceValidator extends Validator
{
  /**
   * validateNonSpace 半角/全角スペースのバリデーション(スペースのみを拒否)
   *
   * @param string $value
   * @access public
   * @return bool
   */
  public function validateNonSpace($attribute, $value, $parameters)
  {
    return trim(mb_convert_kana($value, "s", 'UTF-8')) !== '';
  }
}
