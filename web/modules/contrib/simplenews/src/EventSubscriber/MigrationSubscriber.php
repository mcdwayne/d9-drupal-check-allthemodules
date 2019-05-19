<?php

namespace Drupal\simplenews\EventSubscriber;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Create a simplenews field on relevant content types.
 *
 * Since simplenews configuration on a node is stored as a field, it has to
 * be added explicitly during a migration. This listener checks if the node
 * type is a simplenews content type and adds the field.
 */
class MigrationSubscriber implements EventSubscriberInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new migration subscriber.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager service.
   */
  public function __construct(EntityFieldManagerInterface $entityFieldManager) {
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * Create simplenews field if applicable.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The event object.
   */
  public function onMigrationPostRowSave(MigratePostRowSaveEvent $event) {
    if (!$event->getRow()->getSourceProperty('simplenews_content_type')) {
      return;
    }

    $node_type = reset($event->getDestinationIdValues());
    $fields = $this->entityFieldManager->getFieldDefinitions('node', $node_type);
    if (isset($fields['simplenews_issue'])) {
      return;
    }

    // If checked and the field does not exist yet, create it.
    $field_storage = FieldStorageConfig::loadByName('node', 'simplenews_issue');
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'label' => t('Issue'),
      'bundle' => $node_type,
      'translatable' => TRUE,
    ]);
    $field->save();

    // Set the default widget.
    entity_get_form_display('node', $node_type, 'default')
      ->setComponent($field->getName())
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $return = [];
    if (class_exists('\Drupal\migrate\Event\MigrateEvents')) {
      $return[MigrateEvents::POST_ROW_SAVE][] = 'onMigrationPostRowSave';
    }
    return $return;
  }
}
