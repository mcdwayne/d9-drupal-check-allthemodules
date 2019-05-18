<?php

namespace Drupal\commerce_migrate_ubercart\EventSubscriber;

use Drupal\commerce_migrate_ubercart\Plugin\migrate\source\ProfileBilling;
use Drupal\profile\Entity\Profile as ProfileEntity;
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
   * If the profile exists, then set it as a new revision.
   *
   * @param \Drupal\migrate\Event\MigratePreRowSaveEvent $event
   *   The event object.
   */
  public function onPreRowSave(MigratePreRowSaveEvent $event) {
    $migration = $event->getMigration();
    $source_plugin = $migration->getSourcePlugin();

    if (is_a($source_plugin, ProfileBilling::class)) {
      if ($profile_id = $event->getRow()->getDestinationProperty('profile_id')) {
        if ($profile = ProfileEntity::load($profile_id)) {
          $profile->setNewRevision();
        }
      }
    }
  }

}
