<?php

namespace Drupal\acquia_contenthub_publisher\EventSubscriber\Config;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\language\Config\LanguageConfigOverrideCrudEvent;
use Drupal\language\Config\LanguageConfigOverrideEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to enqueue candidate Config Entity for publishing.
 *
 * @package acquia_contenthub_publisher
 */
class ConfigSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    if (!class_exists(LanguageConfigOverrideEvents::class)) {
      return [];
    }

    $events[LanguageConfigOverrideEvents::SAVE_OVERRIDE] = 'onOverrideChange';
    $events[LanguageConfigOverrideEvents::DELETE_OVERRIDE] = 'onOverrideChange';

    return $events;
  }

  /**
   * Enqueues candidate entity when a configuration override changed.
   *
   * Enqueues candidate configuration entity for publishing on language override
   * change - either on save or delete.
   *
   * @param \Drupal\language\Config\LanguageConfigOverrideCrudEvent $event
   *   The language configuration override event.
   *
   * @throws \Exception
   */
  public function onOverrideChange(LanguageConfigOverrideCrudEvent $event) {
    $config_name = $event->getLanguageConfigOverride()->getName();

    /** @var \Drupal\Core\Config\Entity\ConfigEntityType $entity_type_definition */
    foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type => $entity_type_definition) {
      if (!$entity_type_definition instanceof ConfigEntityType) {
        continue;
      }

      $config_prefix = $entity_type_definition->getConfigPrefix();
      if (0 === strpos($config_name, $config_prefix)) {
        $id = ConfigEntityStorage::getIDFromConfigName($config_name, $config_prefix);
        $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($id);
        if (!$entity) {
          return;
        }

        _acquia_contenthub_publisher_enqueue_entity($entity, 'update');
        return;
      }
    }
  }

}
