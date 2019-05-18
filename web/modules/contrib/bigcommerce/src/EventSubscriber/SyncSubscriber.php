<?php

namespace Drupal\bigcommerce\EventSubscriber;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Subscriber to handle syncing the Commerce and BigCommerce carts.
 */
class SyncSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValue;

  /**
   * SyncSubscriber constructor.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $keyValue
   */
  public function __construct(KeyValueFactoryInterface $keyValue) {
    $this->keyValue = $keyValue;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[MigrateEvents::POST_IMPORT][] = ['postImport'];

    return $events;
  }

  /**
   * Sets the migrate_last_imported state value.
   *
   * This leverages the same date as migrate_tools so we can copy their code and
   * everything works as expected.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event.
   *
   * @see \Drupal\migrate_tools\MigrateExecutable::onPostImport()
   */
  public function postImport(MigrateImportEvent $event) {
    $migration = $event->getMigration();
    if (in_array('BigCommerce', $migration->getMigrationTags(), TRUE)) {
      $migrate_last_imported_store = $this->keyValue->get('migrate_last_imported');
      $migrate_last_imported_store->set($migration->id(), round(microtime(TRUE) * 1000));
    }
  }

}
