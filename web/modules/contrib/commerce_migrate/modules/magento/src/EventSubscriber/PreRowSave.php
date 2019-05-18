<?php

namespace Drupal\commerce_migrate_magento\EventSubscriber;

use Drupal\profile\Entity\Profile;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles order and order variation references.
 *
 * @package \Drupal\commerce_migrate\EventSubscriber
 */
class PreRowSave implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::PRE_ROW_SAVE][] = 'onPreRowSave';
    return $events;
  }

  /**
   * Reacts to the PRE_ROW_SAVE event.
   *
   * @param \Drupal\migrate\Event\MigratePreRowSaveEvent $event
   *   The migrate pre-row-save event.
   */
  public function onPreRowSave(MigratePreRowSaveEvent $event) {
    $migration = $event->getMigration();
    $destination_configuration = $migration->getDestinationConfiguration();

    if ($destination_configuration['plugin'] === 'entity:profile') {
      if ($profile_id = $event->getRow()
        ->getDestinationProperty('profile_id')) {
        if ($profile = Profile::load($profile_id)) {
          $profile->setNewRevision();
        }
      }
    }
  }

}
