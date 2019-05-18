<?php

namespace Drupal\odoo_api\Event;

/**
 * Odoo API failed call event.
 */
class OdooApiFailedCallEvent extends OdooApiCallBaseEvent {

  /**
   * Name of the failed event fired.
   *
   * This event allows modules to perform an action whenever the event will be
   * triggered. The event listener method receives
   * a \Drupal\odoo_api_entity_sync\Event\OdooApiFailedCallEvent instance.
   *
   * @Event
   *
   * @var string
   */
  const EVENT_NAME = 'odoo_api.failed_call';

  /**
   * Failed API call exception.
   *
   * @var object|null
   */
  protected $exception;

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
   *   Odoo API call operation time.
   * @param object|null $exception
   *   Failed API call exception or NULL.
   */
  public function __construct($model_name, $model_method, $odoo_user, array $arguments, array $named_arguments, $time, $exception) {
    parent::__construct($model_name, $model_method, $odoo_user, $arguments, $named_arguments, $time);
    $this->exception = $exception;
  }

  /**
   * Failed API call exception getter.
   *
   * @return object|null
   *   Failed API call exception.
   */
  public function getException() {
    return $this->exception;
  }

}
