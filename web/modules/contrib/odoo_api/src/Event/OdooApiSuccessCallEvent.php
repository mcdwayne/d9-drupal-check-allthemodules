<?php

namespace Drupal\odoo_api\Event;

/**
 * Odoo API success call event.
 */
class OdooApiSuccessCallEvent extends OdooApiCallBaseEvent {

  /**
   * Name of the success event fired.
   *
   * This event allows modules to perform an action whenever the event will be
   * triggered. The event listener method receives
   * a \Drupal\odoo_api_entity_sync\Event\OdooApiSuccessCallEvent instance.
   *
   * @Event
   *
   * @var string
   */
  const EVENT_NAME = 'odoo_api.success_call';

  /**
   * Xml RPC call response.
   *
   * @var mixed
   */
  protected $response;

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
   * @param mixed $response
   *   Xml RPC call response.
   * @param string $time
   *   Odoo API call operation time.
   */
  public function __construct($model_name, $model_method, $odoo_user, array $arguments, array $named_arguments, $response, $time) {
    parent::__construct($model_name, $model_method, $odoo_user, $arguments, $named_arguments, $time);
    $this->response = $response;
  }

  /**
   * Xml RPC call response getter.
   *
   * @return mixed
   *   Xml RPC call response.
   */
  public function getResponse() {
    return $this->response;
  }

}
