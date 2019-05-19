<?php

namespace Drupal\themekey\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\themekey\RuleChainManagerTrait;

/**
 * Defines a event listener implementation for config import.
 */
class ConfigImportEventSubscriber implements EventSubscriberInterface {

  use RuleChainManagerTrait;

  public function onConfigImporterValidate(ConfigImporterEvent $event) {
    // TODO validate chain: check if all rules exist / are already imported
  }

  public function onConfigImporterSave(ConfigCrudEvent $event) {
    $ruleChainManager = $this->getRuleChainManager();
    $ruleChainManager->rebuildOptimizedChain(
      $event->getConfig()->get('chain')
    );
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[ConfigEvents::IMPORT_VALIDATE][] = array('onConfigImporterValidate', 20);
    $events[ConfigEvents::SAVE][] = array('onConfigImporterSave', 20);
    return $events;
  }
}
