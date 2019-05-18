<?php

/**
 * @file
 * Contains \Drupal\menu_link_weight\EventSubscriber\ConfigSubscriber.
 */

namespace Drupal\menu_link_weight\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\DrupalKernelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigSubscriber implements EventSubscriberInterface {

  /**
   * The Drupal Kernel.
   *
   * @var \Drupal\Core\DrupalKernelInterface
   */
  protected $kernel;

  /**
   * ConfigSubscriber constructor.
   *
   * @param \Drupal\Core\DrupalKernelInterface $kernel
   *   The Drupal Kernel.
   */
  public function __construct(DrupalKernelInterface $kernel) {
    $this->kernel = $kernel;
  }

  /**
   * Causes the container to be rebuilt on the next request.
   *
   * @param ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $saved_config = $event->getConfig();
    if ($saved_config->getName() == 'menu_link_weight.settings' && $event->isChanged('menu_parent_form_selector')) {
      $this->kernel->invalidateContainer();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onConfigSave', 0];
    return $events;
  }

}
