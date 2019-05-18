<?php
/**
 * Created by PhpStorm.
 * User: buyle
 * Date: 8/16/16
 * Time: 9:50 AM
 */

namespace Drupal\lightspeed_ecom\Service;

use Drupal\Core\Url;
use Drupal\lightspeed_ecom\Entity\Shop;


/**
 * Represent the information about a webhook.
 *
 * This is an immutable class, instances are pure data objects.
 *
 * @internal
 * @package Drupal\lightspeed_ecom\Service
 */
class Webhook {

  const STATUS_ACTIVE = 'active';
  const STATUS_INACTIVE = 'inactive';
  const STATUS_UNREGISTERED = 'unregistered';
  const STATUS_UNKNOWN = 'unknown';


  /** @var  string The item group. Available groups: customers, orders, invoices, shipments, products, variants, quotes, reviews, returns, tickets, subscriptions, contacts.  */
  protected $group;

  /** @var  string The item action. */
  protected $action;

  /** @var  string The status of this Webhook, one of self::STATUS_* */
  protected $status;

  /** @var string The unique numeric identifier for the webhook, only if registered.  */
  protected $id;

  /** @var string  Webhook URL that will called when the event is triggered. */
  protected $address;

  /** @var  string[] The list of listeners this Webhooks */
  protected $listeners;

  /**
   * Create a new Webhook.
   *
   * @param string $group
   * @param string $action
   */
  public function __construct($group, $action, $services, $address, $status = self::STATUS_UNKNOWN, $id = NULL) {
    $this->group = $group;
    $this->action = $action;
    $this->listeners = $services;
    $this->status = $status;
    $this->id = $id;
    $this->address = $address;
  }


  /**
   * @return string
   */
  public function getGroup() {
    return $this->group;
  }

  /**
   * @return string
   */
  public function getAction() {
    return $this->action;
  }

  /**
   * @return string
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * @return string
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @return string
   */
  public function getAddress() {
    return $this->address;
  }

  /**
   * Returns the listeners for this webhook, as an array of strings.
   *
   * The listeners are returned as string, instead of callable on prupose. They
   * are provided for information only, not for processing of the webhook.
   *
   * @return string[]
   *   The listeners for this webhook, as an array of strings.
   */
  public function getListeners() {
    return $this->listeners;
  }



}
