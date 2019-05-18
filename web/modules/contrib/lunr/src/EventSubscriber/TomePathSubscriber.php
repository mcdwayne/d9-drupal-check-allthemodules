<?php

namespace Drupal\lunr\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\tome_static\Event\CollectPathsEvent;
use Drupal\tome_static\Event\TomeStaticEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds index filenames for Tome exports.
 */
class TomePathSubscriber implements EventSubscriberInterface {

  /**
   * The Lunr search entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $lunrSearchStorage;

  /**
   * Constructs the EntityPathSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->lunrSearchStorage = $entity_type_manager->getStorage('lunr_search');
  }

  /**
   * Reacts to a collect paths event.
   *
   * @param \Drupal\tome_static\Event\CollectPathsEvent $event
   *   The collect paths event.
   */
  public function collectPaths(CollectPathsEvent $event) {
    /** @var \Drupal\lunr\LunrSearchInterface $search */
    foreach ($this->lunrSearchStorage->loadMultiple() as $search) {
      $directory = dirname($search->getBaseIndexPath());
      foreach (array_keys(file_scan_directory($directory, '/.*/')) as $filename) {
        $event->addPath(file_create_url($filename), ['language_processed' => 'language_processed']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[TomeStaticEvents::COLLECT_PATHS][] = ['collectPaths'];
    return $events;
  }

}
