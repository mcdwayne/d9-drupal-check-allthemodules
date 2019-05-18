<?php

namespace Drupal\odoo_api\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Base Odoo API call event.
 */
class OdooApiCallBaseEvent extends Event {

  /**
   * Odoo model name.
   *
   * @var string
   */
  protected $modelName;

  /**
   * Odoo model method name.
   *
   * @var string
   */
  protected $modelMethod;

  /**
   * Odoo user ID.
   *
   * @var int
   */
  protected $odooUser;

  /**
   * Odoo model method arguments.
   *
   * @var array
   */
  protected $arguments;

  /**
   * Odoo model method named arguments.
   *
   * @var array
   */
  protected $namedArguments;

  /**
   * Odoo API call operation time.
   *
   * @var string
   */
  protected $time;

  /**
   * Event constructor.
   *
   * @param string $model_name
   *   Odoo model name, like 'res.partner'.
   * @param string $model_method
   *   Odoo model method name; a 'search', 'read' or similar.
   * @param int $odoo_user
   *   Odoo user ID.
   * @param array $arguments
   *   Odoo model method arguments.
   * @param array $named_arguments
   *   Odoo model method named arguments.
   * @param string $time
   *   Odoo API call operation time in milliseconds.
   */
  public function __construct($model_name, $model_method, $odoo_user, array $arguments, array $named_arguments, $time) {
    $this->modelName = $model_name;
    $this->modelMethod = $model_method;
    $this->odooUser = $odoo_user;
    $this->arguments = $arguments;
    $this->namedArguments = $named_arguments;
    $this->time = $time;
  }

  /**
   * Odoo model name getter.
   *
   * @return string
   *   Odoo model name.
   */
  public function getModelName() {
    return $this->modelName;
  }

  /**
   * Odoo model method getter.
   *
   * @return string
   *   Odoo model method name.
   */
  public function getModelMethod() {
    return $this->modelMethod;
  }

  /**
   * Odoo user ID getter.
   *
   * @return int
   *   Odoo user ID.
   */
  public function getOdooUser() {
    return $this->odooUser;
  }

  /**
   * Odoo model method arguments getter.
   *
   * @return array
   *   Odoo model method arguments.
   */
  public function getMethodArguments() {
    return $this->arguments;
  }

  /**
   * Odoo model method named arguments getter.
   *
   * @return array
   *   Odoo model method named arguments.
   */
  public function getNamedMethodArguments() {
    return $this->namedArguments;
  }

  /**
   * Odoo API call operation time getter.
   *
   * @return string
   *   Odoo API call operation time in milliseconds.
   */
  public function getTime() {
    return $this->time;
  }

}
