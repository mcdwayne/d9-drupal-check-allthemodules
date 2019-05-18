<?php

namespace Drupal\acquia_contenthub\EventSubscriber\UnserializeContentField;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\UnserializeCdfEntityFieldEvent;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\layout_builder\Plugin\Block\InlineBlock;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Layout builder field unserializer fallback subscriber.
 */
class LayoutBuilderFieldUnserializer implements EventSubscriberInterface {

  protected $fieldType = 'layout_section';

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
    $events[AcquiaContentHubEvents::UNSERIALIZE_CONTENT_ENTITY_FIELD] = ['onUnserializeContentField'];
    return $events;
  }

  /**
   * Handling for Layout Builder sections.
   *
   * @param \Drupal\acquia_contenthub\Event\UnserializeCdfEntityFieldEvent $event
   *   The unserialize event.
   */
  public function onUnserializeContentField(UnserializeCdfEntityFieldEvent $event) {
    $event_field_type = $event->getFieldMetadata()['type'];
    if ($event_field_type !== $this->fieldType) {
      return;
    }

    $field = $event->getField();
    $values = [];
    if (!empty($field['value'])) {
      foreach ($field['value'] as $langcode => $sections) {
        $values[$langcode][$event->getFieldName()] = $this->handleSections($sections);
      }
      $event->setValue($values);
    }
    $event->stopPropagation();
  }

  /**
   * Prepares Layout Builder sections to be unserialized.
   *
   * @param array $sections
   *   The Layout Builder sections to unserialize.
   *
   * @return array
   *   The prepared sections.
   */
  protected function handleSections(array $sections) {
    $values = [];
    foreach ($sections as $sectionArray) {
      $section = Section::fromArray($sectionArray['section']);
      $this->handleComponents($section->getComponents());
      $values[] = ['section' => $section];
    }
    return $values;
  }

  /**
   * Prepares Layout Builder components to be unserialized.
   *
   * @param \Drupal\layout_builder\SectionComponent[] $components
   *   The components to unserialize.
   */
  protected function handleComponents(array $components) {
    foreach ($components as $component) {
      $plugin = $component->getPlugin();
      // @todo Decide if it's worth to handle this as an event.
      if ($plugin instanceof InlineBlock) {
        $block_uuid = $component->get('block_uuid');
        $entity = array_shift($this->entityTypeManager->getStorage('block_content')->loadByProperties(['uuid' => $block_uuid]));
        $componentConfiguration = $this->getComponentConfiguration($component);
        $componentConfiguration['block_revision_id'] = $entity->getRevisionId();
        $component->setConfiguration($componentConfiguration);
      }
    }
  }

  /**
   * Gets configuration for a Layout Builder component.
   *
   * @param \Drupal\layout_builder\SectionComponent $component
   *   The Layout Builder component.
   *
   * @return array
   *   The component configuration.
   *
   * @throws \ReflectionException
   *
   * @todo Check pending patch to make SectionComponent::getConfiguration() public: https://www.drupal.org/project/drupal/issues/3046814
   */
  protected function getComponentConfiguration(SectionComponent $component) {
    $method = new \ReflectionMethod('\Drupal\layout_builder\SectionComponent', 'getConfiguration');
    $method->setAccessible(TRUE);

    return $method->invoke($component);
  }

}
