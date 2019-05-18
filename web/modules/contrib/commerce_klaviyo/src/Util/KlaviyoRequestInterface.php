<?php

namespace Drupal\commerce_klaviyo\Util;

/**
 * Interface KlaviyoRequestInterface.
 *
 * @package Drupal\commerce_klaviyo\Util
 */
interface KlaviyoRequestInterface {

  const VIEWED_PRODUCT_EVENT = 'Viewed Product';
  const STARTED_CHECKOUT_EVENT = 'Started Checkout';
  const PLACED_ORDER_EVENT = 'Placed Order';
  const ORDERED_PRODUCT_EVENT = 'Ordered Product';
  const FULFILLED_ORDER_EVENT = 'Fulfilled Order';

  const CRON_QUEUE = 'klaviyo_request';

  /**
   * Sends the track call to Klaviyo.
   *
   * @param string $event_name
   *   The event name.
   * @param \Drupal\commerce_klaviyo\Util\KlaviyoRequestPropertiesInterface $customer_properties
   *   The customer properties.
   * @param \Drupal\commerce_klaviyo\Util\KlaviyoRequestPropertiesInterface $properties
   *   The properties send to Klaviyo.
   * @param int|null $timestamp
   *   The timestamp when this event occurred.
   * @param bool $track_later
   *   Whether the event tracking should be queue for Cron. Defaults to
   *   immediate processing.
   */
  public function track($event_name, KlaviyoRequestPropertiesInterface $customer_properties, KlaviyoRequestPropertiesInterface $properties, $timestamp = NULL, $track_later = FALSE);

  /**
   * Sends "identify" request to Klaviyo.
   *
   * @param \Drupal\commerce_klaviyo\Util\KlaviyoRequestPropertiesInterface $customer_properties
   *   The customer properties.
   */
  public function identify(KlaviyoRequestPropertiesInterface $customer_properties);

  /**
   * Alters customer properties for the identify request.
   *
   * @param array $customer_properties
   *   The customer properties.
   *
   * @return array
   *   The altered customer properties.
   */
  public function alterIdentify(array $customer_properties);

  /**
   * Alters properties for the track request.
   *
   * @param string $event_name
   *   The event name.
   * @param array $properties
   *   The request properties.
   * @param \Drupal\commerce_klaviyo\Util\KlaviyoRequestPropertiesInterface $klaviyo_request_properties
   *   The Klaviyo request properties object.
   *
   * @return array
   *   The altered for the track request.
   */
  public function alterTrack($event_name, array $properties, KlaviyoRequestPropertiesInterface $klaviyo_request_properties);

}
