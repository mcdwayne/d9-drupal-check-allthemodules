<?php

namespace Drupal\commerce_price_rule\Event;

/**
 * Defines the names of events related to price rules.
 */
final class PriceRuleEvents {

  /**
   * Name of event fired when preparing the query to load available price rules.
   *
   * @Event
   *
   * @see \Drupal\commerce_price_rule\Event\LoadAvailablePriceRulesEvent
   */
  const LOAD_AVAILABLE = 'commerce_price_rule.load_available';

}
