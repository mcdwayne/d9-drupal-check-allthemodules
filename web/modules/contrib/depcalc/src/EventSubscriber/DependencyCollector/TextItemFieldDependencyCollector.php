<?php

namespace Drupal\depcalc\EventSubscriber\DependencyCollector;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\depcalc\DependencyCalculatorEvents;
use Drupal\depcalc\DependentEntityWrapper;
use Drupal\depcalc\Event\CalculateEntityDependenciesEvent;
use Drupal\depcalc\FieldExtractor;

/**
 * Subscribes to dependency collection to extract the filter format entity.
 */
class TextItemFieldDependencyCollector extends BaseDependencyCollector {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * TextItemFieldDependencyCollector constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }


  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[DependencyCalculatorEvents::CALCULATE_DEPENDENCIES][] = ['onCalculateDependencies'];
    return $events;
  }

  /**
   * Calculates the associated filter_format entity for any text item field.
   *
   * @param \Drupal\depcalc\Event\CalculateEntityDependenciesEvent $event
   *   The dependency calculation event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function onCalculateDependencies(CalculateEntityDependenciesEvent $event) {
    // @todo determine if there's a better way to catch this field type for other classes which might some day extend it.
    $entity = $event->getEntity();
    if (!$entity instanceof ContentEntityInterface) {
      return;
    }
    $fields = FieldExtractor::getFieldsFromEntity($entity, function (ContentEntityInterface $entity, $field_name, FieldItemListInterface $field) { return in_array($field->getFieldDefinition()->getType(), ['text_with_summary', 'text', 'text_long']); });
    if (!$fields) {
      return;
    }
    /**
     * @var string $field_name
     * @var \Drupal\Core\Field\FieldItemListInterface $field
     */
    foreach ($fields as $field) {
      foreach ($field as $item) {
        $values = $item->getValue();
        if (!empty($values['format']) && $format = $this->entityTypeManager->getStorage('filter_format')
            ->load($values['format'])) {
          $format_wrapper = new DependentEntityWrapper($format);
          $local_dependencies = [];
          $this->mergeDependencies($format_wrapper, $event->getStack(), $this->getCalculator()->calculateDependencies($format_wrapper, $event->getStack(), $local_dependencies));
          $event->addDependency($format_wrapper);
          if (\Drupal::moduleHandler()->moduleExists('editor')) {
            $editor = $format = $this->entityTypeManager->getStorage('editor')
              ->load($values['format']);
            if ($editor) {
              $editor_wrapper = new DependentEntityWrapper($editor);
              $editor_wrapper->addDependency($format_wrapper, $event->getStack());
              $local_dependencies = [];
              $this->mergeDependencies($editor_wrapper, $event->getStack(), $this->getCalculator()->calculateDependencies($editor_wrapper, $event->getStack(), $local_dependencies));
              $event->addDependency($editor_wrapper);
              $event->setModuleDependencies(['editor', 'ckeditor']);
            }
          }
        }
      }
    }
  }

}
