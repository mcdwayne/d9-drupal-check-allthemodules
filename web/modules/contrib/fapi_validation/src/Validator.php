<?php

namespace Drupal\fapi_validation;

/**
 * Validator Class to parse Form Element validators content.
 */
class Validator {
  /**
   * Raw validator content.
   *
   * @var array|string
   */
  private $rawValidator;

  /**
   * Form element value.
   *
   * @var string
   */
  private $value;

  /**
   * Rule name.
   *
   * @var string
   */
  private $name;

  /**
   * Rule parameters.
   *
   * @var array
   */
  private $params = [];

  /**
   * User defined error message.
   *
   * @var string
   */
  private $errorMessage;

  /**
   * User defined error callback.
   *
   * @var string
   */
  private $errorCallback;

  /**
   * Create object and parse validator data.
   *
   * @param array|string $raw_validator
   *   Raw user defined validator.
   * @param string $value
   *   Form element value.
   */
  public function __construct($raw_validator, $value) {
    $this->rawValidator = $raw_validator;
    $this->value = $value;
    $this->parse();
  }

  /**
   * Parse user defined validator.
   */
  private function parse() {
    if (is_array($this->rawValidator)) {
      if (isset($this->rawValidator['error'])) {
        $this->error_message = $this->rawValidator['error'];
      }

      if (isset($this->rawValidator['error callback'])) {
        $this->error_callback = $this->rawValidator['error callback'];
      }

      if (!isset($this->rawValidator['rule'])) {
        throw new \LogicException("You can't define a validator as array and don't define 'rule' key.");
      }

      $this->rawValidator = $this->rawValidator['rule'];
    }

    preg_match('/^(.*?)(\[(.*)\])?$/', $this->rawValidator, $rs);

    $this->name = $rs[1];

    if (isset($rs[3])) {
      if ($this->name == 'regexp') {
        $this->params = [$rs[3]];
      }
      else {
        $this->params = preg_split('/ *, */', $rs[3]);
      }
    }
  }

  /**
   * Return Form Element value.
   *
   * @return string
   *   Value.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Return rule name.
   *
   * @return string
   *   Rule name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Return rule parameters.
   *
   * @return array
   *   Params.
   */
  public function getParams() {
    return $this->params;
  }

  /**
   * Check if there is user defined error message.
   *
   * @return bool
   *   Check.
   */
  public function hasErrorMessageDefined() {
    return $this->errorMessage !== NULL;
  }

  /**
   * Get User defined error error message.
   *
   * @return string
   *   Error messaage.
   */
  public function getErrorMessage() {
    return $this->errorMessage;
  }

  /**
   * Check if there is user defined error callback.
   *
   * @return bool
   *   Check.
   */
  public function hasErrorCallbackDefined() {
    return $this->errorMessage !== NULL;
  }

  /**
   * Return user defined error callback.
   *
   * @return string
   *   Erro Callback.
   */
  public function getErrorCallback() {
    return $this->errorCallback;
  }

}
