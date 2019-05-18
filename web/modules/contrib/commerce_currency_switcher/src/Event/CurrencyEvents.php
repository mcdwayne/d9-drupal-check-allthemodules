<?php

namespace Drupal\commerce_currency_switcher\Event;

/**
 * Defines events for the Commerce Multicurrency module.
 */
final class CurrencyEvents {

  /**
   * Name of the event fired when resolving a Geoip based currency.
   *
   * This event allows modules to alter the Geoip currency resolved before it's
   * returned and used by the system. The event listener method receives a
   * \Drupal\commerce_currency_switcher\Event\GeoipCurrencyResolveEvent instance.
   *
   * @Event
   *
   * @see \Drupal\commerce_currency_switcher\Event\GeoipCurrencyResolveEvent
   */
  const GEOIP_CURRENCY_RESOLVE = 'commerce_multicurrency.currency.geoip_resolve';

  /**
   * Name of the event fired when converting a currency.
   *
   * This event allows modules to alter the converted currency before it's
   * returned and used by the system. The event listener method receives a
   * \Drupal\commerce_price\Event\NumberFormatEvent instance.
   *
   * @Event
   *
   * @see \Drupal\commerce_price\Event\NumberFormatEvent
   */
  const CURRENCY_CONVERT = 'commerce_multicurrency.currency.convert';

}
