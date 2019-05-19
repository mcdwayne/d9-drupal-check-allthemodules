<?php

namespace Drupal\yac_referral;

use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Config\Config;

/**
 * Event class to be dispatched from the NewSubscription service.
 */
class NewSubscriptionEvent extends Event {

  const EVENT = 'yac_referral.new_subscription';

  /**
   * The total number of subscriptions.
   *
   * @var int
   */
  protected $subscriptions;

  protected $config;

  public function __construct(Config $config) {
    $this->config = $config;
  }

  public function getValue() {
    return $this->subscriptions;
  }

  public function setValue() {
    $subscriptions++;
  }
}
