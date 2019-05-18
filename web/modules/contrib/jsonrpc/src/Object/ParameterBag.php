<?php

namespace Drupal\jsonrpc\Object;

/**
 * Value class to hold multiple parameters.
 */
class ParameterBag {

  /**
   * True if the params in the bag are positional. They have sequential keys.
   *
   * @var bool
   */
  protected $positional;

  /**
   * The parameters in the bag.
   *
   * @var mixed[]
   *   The parameters.
   */
  protected $parameters;

  /**
   * ParameterBag constructor.
   *
   * @param array $parameters
   *   The parameters.
   * @param bool $positional
   *   True if the parameters are positional.
   */
  public function __construct(array $parameters, $positional = FALSE) {
    $this->positional = $positional;
    $this->parameters = $positional ? array_values($parameters) : $parameters;
  }

  /**
   * Gets the parameter value by its key.
   *
   * @param string|int $key
   *   The parameter key.
   *
   * @return mixed
   *   The parameter.
   */
  public function get($key) {
    $this->ensure($key);
    return isset($this->parameters[$key]) ? $this->parameters[$key] : NULL;
  }

  /**
   * Checks if the bag has a parameter.
   *
   * @param string|int $key
   *   The parameter key.
   *
   * @return bool
   *   True if the param is present.
   */
  public function has($key) {
    $this->checkKeyIsValid($key);
    return isset($this->parameters[$key]);
  }

  /**
   * Checks if the parameter bag is empty.
   *
   * @return bool
   *   True if the bag is empty.
   */
  public function isEmpty() {
    return empty($this->parameters);
  }

  /**
   * Throw an exception if the bag does not have the parameter.
   *
   * @throws \InvalidArgumentException
   *   When the parameter is not present in the bag.
   */
  protected function ensure($key) {
    $this->checkKeyIsValid($key);
  }

  /**
   * Checks if the key is valid.
   *
   * @throws \InvalidArgumentException
   *   If the key is not valid.
   */
  protected function checkKeyIsValid($key) {
    if ($this->positional && !is_int($key) && $key >= 0) {
      throw new \InvalidArgumentException('The parameters are by-position. Integer key required.');
    }
    elseif (!is_string($key)) {
      throw new \InvalidArgumentException('The parameters are by-name. String key required.');
    }
  }

}
