<?php
namespace Drupal\forgot_password\Validator;

/**
 * Class ValidatorRequired.
 *
 * @package Drupal\forgot_password\Validator
 */
class ValidatorRequired extends BaseValidator {
	/**
	 * {@inheritdoc}
	*/
	public function validates($field, $value) {
    if($field == 'user_email') {
      if($value == NULL) {
        $result = '';
        return is_array($result) ? !empty(array_filter($result)) : !empty($result);
      }
    }
  }
}