<?php

namespace Drupal\depcalc\EventSubscriber\DependencyCollector;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\depcalc\DependencyCalculatorEvents;
use Drupal\depcalc\DependentEntityWrapper;
use Drupal\depcalc\Event\CalculateEntityDependenciesEvent;
use Drupal\depcalc\FieldExtractor;

/**
 * Subscribes to dependency collection to extract referenced entities.
 */
class EntityReferenceFieldDependencyCollector extends BaseDependencyCollector {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[DependencyCalculatorEvents::CALCULATE_DEPENDENCIES][] = ['onCalculateDependencies'];
    return $events;
  }

  /**
   * Calculates the referenced entities.
   *
   * @param \Drupal\depcalc\Event\CalculateEntityDependenciesEvent $event
   *   The dependency calculation event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function onCalculateDependencies(CalculateEntityDependenciesEvent $event) {
    $entity = $event->getEntity();
    if ($entity instanceof ContentEntityInterface) {
      $fields = FieldExtractor::getFieldsFromEntity($entity, [$this, 'fieldCondition']);
      foreach ($fields as $field) {
        foreach ($field as $item) {
          if (!$item->entity) {
            $sub_entity = \Drupal::entityTypeManager()->getStorage($field->getFieldDefinition()->getSetting('target_type'))->load($item->getValue()['target_id']);
            if (is_null($sub_entity)) {
              continue;
            }
            $item->entity = $sub_entity;
          }
          $item_entity_wrapper = new DependentEntityWrapper($item->entity);
          $local_dependencies = [];
          $this->mergeDependencies($item_entity_wrapper, $event->getStack(), $this->getCalculator()->calculateDependencies($item_entity_wrapper, $event->getStack(), $local_dependencies));
          $event->addDependency($item_entity_wrapper);
        }
      }
    }
  }

  public function fieldCondition(ContentEntityInterface $entity, $field_name, FieldItemListInterface $field) {
    return in_array($field->getFieldDefinition()->getType(), [
      'file',
      'image',
      'entity_reference',
      'entity_reference_revisions'
    ]);
  }

}
