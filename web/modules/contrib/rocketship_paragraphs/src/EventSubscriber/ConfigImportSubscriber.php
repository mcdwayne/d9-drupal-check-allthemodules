<?php

namespace Drupal\rocketship_paragraphs\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ConfigImportSubscriber.
 */
class ConfigImportSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new ConfigImportSubscriber object.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::IMPORT] = ['onConfigImport'];

    return $events;
  }

  /**
   * Called whenever the ConfigEvents::IMPORT event is dispatched.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The fired event.
   */
  public function onConfigImport(ConfigImporterEvent $event) {
    _rocketship_paragraphs_generate_background_css_file();
  }

}
