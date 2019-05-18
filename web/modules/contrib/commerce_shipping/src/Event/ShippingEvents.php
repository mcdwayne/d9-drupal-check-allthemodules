<?php

namespace Drupal\commerce_shipping\Event;

final class ShippingEvents {

  /**
   * Name of the event fired when shipping methods are loaded for a shipment.
   *
   * @Event
   *
   * @see \Drupal\commerce_shipping\Event\FilterShippingMethodsEvent
   */
  const FILTER_SHIPPING_METHODS = 'commerce_shipping.filter_shipping_methods';

}
