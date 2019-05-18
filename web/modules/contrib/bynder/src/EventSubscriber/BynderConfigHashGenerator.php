<?php

namespace Drupal\bynder\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A subscriber that generates Bynder config hash.
 *
 * Hash is used to validate active user sessions.
 */
class BynderConfigHashGenerator implements EventSubscriberInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a BynderConfigHashGenerator object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * Generates hash based on active Bynder config and saves it into state.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The Event to process.
   */
  public function onChange(ConfigCrudEvent $event) {
    if (strpos($event->getConfig()->getName(), 'bynder.settings') === 0) {
      $hash_source = [];
      foreach (['consumer_key', 'consumer_secret', 'token', 'token_secret', 'account_domain'] as $key) {
        $hash_source[] = $event->getConfig()->get($key);
      }

      $this->state->set('bynder_config_hash', md5(implode(':', $hash_source)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onChange'];
    return $events;
  }

}
