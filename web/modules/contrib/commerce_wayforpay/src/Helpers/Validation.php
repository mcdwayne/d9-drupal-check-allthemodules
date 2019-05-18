<?php

namespace Drupal\commerce_wayforpay\Helpers;

/**
 * Class Validation.
 *
 * @package Drupal\commerce_wayforpay\Helpers
 */
class Validation {

  /**
   * Rules.
   *
   * @var array
   */
  public $rules = [];

  /**
   * Validation data.
   *
   * @var array
   */
  public $data;

  /**
   * Errors.
   *
   * @var array
   */
  public $errors = [];

  /**
   * Constructor.
   *
   * @param array $data
   *   Validation Data.
   */
  public function __construct(array $data) {
    $this->data = $data;
  }

  /**
   * Factory.
   *
   * @param array $data
   *   Validation data.
   *
   * @return Validation
   *   Validation instance.
   */
  public static function factory(array $data) {
    return new self($data);
  }

  /**
   * Add rule.
   *
   * @param string $field
   *   Field name.
   * @param string $rule
   *   Rule.
   * @param array $params
   *   Params.
   *
   * @return Validation
   *   Self.
   */
  public function rule($field, $rule, array $params = []) {
    $this->rules[$field][$rule] = $params;
    return $this;
  }

  /**
   * Perform validation.
   *
   * @return bool
   *   Result.
   *
   * @throws \Exception
   */
  public function check() {
    foreach ($this->rules as $field => $rules) {

      foreach ($rules as $rule => $params) {
        switch ($rule) {
          case 'not_empty':
            if (empty($this->data[$field])) {
              $this->errors[] = "Field {$field} must be not empty";
            }
            break;

          case 'equals':
            if (@$this->data[$field] != $params[':value']) {
              $this->errors[] = "Field {$field} must equal {$params[':value']}";
            }
            break;

          default:
            throw new \Exception("Validatation rule {$rule} not supported");
        }
      }
    }
    return empty($this->errors);
  }

}
