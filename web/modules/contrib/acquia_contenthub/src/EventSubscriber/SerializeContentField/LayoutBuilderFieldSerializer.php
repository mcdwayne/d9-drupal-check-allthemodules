<?php

namespace Drupal\acquia_contenthub\EventSubscriber\SerializeContentField;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\layout_builder\Plugin\Block\InlineBlock;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to entity field serialization to handle layout builder fields.
 */
class LayoutBuilderFieldSerializer implements EventSubscriberInterface {

  use ContentFieldMetadataTrait;

  const FIELD_TYPE = 'layout_section';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * LayoutBuilderFieldSerializer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::SERIALIZE_CONTENT_ENTITY_FIELD][] = ['onSerializeContentField'];
    return $events;
  }

  /**
   * Prepare layout builder field.
   *
   * @param \Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent $event
   *   The content entity field serialization event.
   */
  public function onSerializeContentField(SerializeCdfEntityFieldEvent $event) {
    $event_field_type = $event->getField()->getFieldDefinition()->getType();
    if ($event_field_type !== self::FIELD_TYPE) {
      return;
    }

    $this->setFieldMetaData($event);
    $data = [];
    /** @var \Drupal\Core\Entity\TranslatableInterface $entity */
    $entity = $event->getEntity();
    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      $field = $event->getFieldTranslation($langcode);

      if ($field->isEmpty()) {
        $data['value'][$langcode] = [];
        continue;
      }

      $data['value'][$langcode] = $this->handleSections($field);
    }
    $event->setFieldData($data);
    $event->stopPropagation();
  }

  /**
   * Prepares Layout Builder sections to be serialized.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field with the sections.
   *
   * @return array
   *   The prepared Layout Builder sections.
   */
  protected function handleSections(FieldItemListInterface $field) {
    $sections = [];
    foreach ($field as $item) {
      $section = $item->getValue()['section'];
      $this->handleComponents($section->getComponents());
      $sections[] = ['section' => $section->toArray()];
    }
    return $sections;
  }

  /**
   * Prepares component to be serialized.
   *
   * @param \Drupal\layout_builder\SectionComponent[] $components
   *   The component to add.
   */
  protected function handleComponents(array $components) {
    foreach ($components as $component) {
      $plugin = $component->getPlugin();
      // @todo Decide if it's worth to handle this as an event.
      if ($plugin instanceof InlineBlock) {
        $revision_id = $plugin->getConfiguration()['block_revision_id'];
        $entity = $this->entityTypeManager->getStorage('block_content')->loadRevision($revision_id);
        $component->set('block_uuid', $entity->uuid());
      }
    }
  }

}
