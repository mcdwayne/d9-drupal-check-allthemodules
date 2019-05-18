<?php

namespace Drupal\multiversion\EventSubscriber;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\State\StateInterface;
use Drupal\multiversion\Event\MultiversionManagerEvent;
use Drupal\multiversion\Event\MultiversionManagerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Creating a flag to skip generating new alias.
 */
class PathAutoAliasSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * Set flag for disabling alias generate.
   *
   * @param \Drupal\multiversion\Event\MultiversionManagerEvent $event
   */
  public function onPreMigrate(MultiversionManagerEvent $event) {
    $this->state->set('skip_alias_save', TRUE);
  }

  /**
   * Remove flag "skip_pathauto_generator".
   *
   * @param \Drupal\multiversion\Event\MultiversionManagerEvent $event
   */
  public function onPostMigrate(MultiversionManagerEvent $event) {
    $this->state->delete('skip_alias_save');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MultiversionManagerEvents::PRE_MIGRATE => ['onPreMigrate'],
      MultiversionManagerEvents::POST_MIGRATE => ['onPostMigrate']
    ];
  }

}
