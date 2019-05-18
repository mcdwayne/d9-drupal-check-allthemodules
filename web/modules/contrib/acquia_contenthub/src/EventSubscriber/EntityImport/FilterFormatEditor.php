<?php

namespace Drupal\acquia_contenthub\EventSubscriber\EntityImport;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\EntityImportEvent;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles FilterFormatEditor entity saves to apply related schema.
 */
class FilterFormatEditor implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::ENTITY_IMPORT_NEW][] = 'onImportNew';
    $events[AcquiaContentHubEvents::ENTITY_IMPORT_UPDATE][] = 'onImportNew';
    return $events;
  }

  /**
   * Ensures an editor exists for a filter format.
   *
   * @param \Drupal\acquia_contenthub\Event\EntityImportEvent $event
   *   The entity import event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onImportNew(EntityImportEvent $event) {
    if (!\Drupal::moduleHandler()->moduleExists('editor')) {
      return;
    }
    $filter_format = $event->getEntity();
    // Early return if this isn't the class of entity we care about.
    if (!$filter_format instanceof FilterFormat) {
      return;
    }
    if (editor_load($filter_format->id())) {
      return;
    }
    $values = [
      'editor' => 'ckeditor',
      'format' => $filter_format->id(),
    ];
    $editor = Editor::create($values);
    $editor->save();
  }

}
