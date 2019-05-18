<?php
/**
 * Created by PhpStorm.
 * User: buyle
 * Date: 7/24/16
 * Time: 8:01 PM
 */

namespace Drupal\lightspeed_ecom\Service;


use Symfony\Component\EventDispatcher\Event;

/**
 * A Webhook event.
 *
 * Contains information about a received Webhook event.
 *
 * Consumer of the module are expected to consume instances of this class but
 * not create them.
 */
class WebhookEvent extends Event {

  const EVENT_NAMESPACE = "lightspeed_ecom";

  /** Name of the event fired when a customer is created. */
  const CUSTOMERS_CREATED = self::EVENT_NAMESPACE . ".customers.created";

  /** Name of the event fired when a customer is updated. */
  const CUSTOMERS_UPDATED = self::EVENT_NAMESPACE . ".customers.updated";

  /** Name of the event fired when a customer is deleted. */
  const CUSTOMERS_DELETED = self::EVENT_NAMESPACE . ".customers.deleted";

  /** Name of the event fired when a order is created. */
  const ORDERS_CREATED = self::EVENT_NAMESPACE . ".orders.created";

  /** Name of the event fired when a order is updated. */
  const ORDERS_UPDATED = self::EVENT_NAMESPACE . ".orders.updated";

  /** Name of the event fired when a order is deleted. */
  const ORDERS_DELETED = self::EVENT_NAMESPACE . ".orders.deleted";

  /** Name of the event fired when a order is paid. */
  const ORDERS_PAID = self::EVENT_NAMESPACE . ".customers.paid";

  /** Name of the event fired when a order is shipped. */
  const ORDERS_SHIPPED = self::EVENT_NAMESPACE . ".customers.shipped";

  /** Name of the event fired when a product is created. */
  const PRODUCTS_CREATED = self::EVENT_NAMESPACE . ".products.created";

  /** Name of the event fired when a product is updated. */
  const PRODUCTS_UPDATED = self::EVENT_NAMESPACE . ".products.updated";

  /** Name of the event fired when a product is deleted. */
  const PRODUCTS_DELETED = self::EVENT_NAMESPACE . ".products.deleted";

  /** Name of the event fired when a quote is created. */
  const QUOTES_CREATED = self::EVENT_NAMESPACE . ".quotes.created";

  /** Name of the event fired when a quote is updated. */
  const QUOTES_UPDATED = self::EVENT_NAMESPACE . ".quotes.updated";

  /** Name of the event fired when a quote is deleted. */
  const QUOTES_DELETED = self::EVENT_NAMESPACE . ".quotes.deleted";

  /** Name of the event fired when a review is created. */
  const REVIEWS_CREATED = self::EVENT_NAMESPACE . ".reviews.created";

  /** Name of the event fired when a review is updated. */
  const REVIEWS_UPDATED = self::EVENT_NAMESPACE . ".reviews.updated";

  /** Name of the event fired when a review is deleted. */
  const REVIEWS_DELETED = self::EVENT_NAMESPACE . ".reviews.deleted";

  /** Name of the event fired when a shipment is created. */
  const SHIPMENTS_CREATED = self::EVENT_NAMESPACE . ".shipments.created";

  /** Name of the event fired when a shipment is updated. */
  const SHIPMENTS_UPDATED = self::EVENT_NAMESPACE . ".shipments.updated";

  /** Name of the event fired when a shipment is deleted. */
  const SHIPMENTS_DELETED = self::EVENT_NAMESPACE . ".shipments.deleted";

  /** Name of the event fired when a subscription is created. */
  const SUBSCRIPTIONS_CREATED = self::EVENT_NAMESPACE . ".subscriptions.created";

  /** Name of the event fired when a subscription is updated. */
  const SUBSCRIPTIONS_UPDATED = self::EVENT_NAMESPACE . ".subscriptions.updated";

  /** Name of the event fired when a subscription is deleted. */
  const SUBSCRIPTIONS_DELETED = self::EVENT_NAMESPACE . ".subscriptions.deleted";

  /** Name of the event fired when a ticket is created. */
  const TICKETS_CREATED = self::EVENT_NAMESPACE . ".tickets.created";

  /** Name of the event fired when a ticket is updated. */
  const TICKETS_UPDATED = self::EVENT_NAMESPACE . ".tickets.updated";

  /** Name of the event fired when a ticket is deleted. */
  const TICKETS_DELETED = self::EVENT_NAMESPACE . ".tickets.deleted";

  /** Name of the event fired when a ticket is answered. */
  const TICKETS_ANSWERED = self::EVENT_NAMESPACE . ".tickets.answered";

  /** Name of the event fired when a variant is created. */
  const VARIANTS_CREATED = self::EVENT_NAMESPACE . ".variants.created";

  /** Name of the event fired when a variant is updated. */
  const VARIANTS_UPDATED = self::EVENT_NAMESPACE . ".variants.updated";

  /** Name of the event fired when a variant is deleted. */
  const VARIANTS_DELETED = self::EVENT_NAMESPACE . ".variants.deleted";

  /** @var  string Group of the event. */
  protected $group;

  /** @var  string Action of the event. */
  protected $action;

  /** @var  string The ID of the shop of the event. */
  protected $shop_id;

  /** @var  string The language of the event. */
  protected $language;

  /** @var string The The ID of the object involved in the event. */
  protected $object_id;

  /** @var  array The object of the event (as an associative array). */
  protected $payload;

  /**
   * WebhookEvent constructor.
   *
   * @param $group
   * @param $action
   * @param $shop_id
   * @param $language
   * @param $object_id
   * @param $payload
   */
  public function __construct($group, $action, $shop_id, $language, $object_id, $payload) {
    $this->group = $group;
    $this->action = $action;
    $this->shop_id = $shop_id;
    $this->language = $language;
    $this->object_id = $object_id;
    $this->payload = $payload;
  }

  /**
   * Returns the group of the event.
   *
   * @return string
   *   The group of the event.
   */
  public function getGroup() {
    return $this->group;
  }

  /**
   * Returns the action of the event.
   *
   * @return string
   *   The action of the event.
   */
  public function getAction() {
    return $this->action;
  }


  /**
   * Returns the ID of the shop of the event.
   *
   * @return string
   *   The ID of the shop of the event.
   */
  public function getShopId() {
    return $this->shop_id;
  }

  /**
   * Returns the language of the event.
   *
   * FIXME: Currently the language code as provided by Lightpseed, but should
   * be mapped to a actual Drupal language object.
   *
   * @return string
   *   The language of the event.
   */
  public function getLanguage() {
    return $this->language;
  }

  /**
   * Returns the ID of the object involved in the event.
   *
   * @return string
   *   The ID of the object involved in the event.
   */
  public function getObjectId() {
    return $this->object_id;
  }

  /**
   * Returns the object involved in the event.
   *
   * @return array
   *   The object involved in the event.
   */
  public function getPayload() {
    return $this->payload;
  }

}
