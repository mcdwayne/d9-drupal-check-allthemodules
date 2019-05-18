<?php

namespace Drupal\entity_switcher;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a item list class for switcher reference fields.
 */
class SwitcherReferenceFieldItemList extends FieldItemList implements SwitcherReferenceFieldItemListInterface {

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();
    $constraint_manager = $this->getTypedDataManager()->getValidationConstraintManager();
    $constraints[] = $constraint_manager->create('ValidSwitcherReference', []);

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {
    if (empty($this->list)) {
      return [];
    }

    // Collect the IDs of existing entities to load, and directly grab the
    // "autocreate" entities that are already populated in $item->entity.
    $target_entities = $ids = [];
    foreach ($this->list as $delta => $referenced_item) {
      foreach (['data_off', 'data_on', 'switcher'] as $item) {
        if ($referenced_item->{$item . '_id'} !== NULL) {
          $ids[$item][$delta] = $referenced_item->{$item . '_id'};
        }
        elseif ($referenced_item->hasNewEntity()) {
          $target_entities[$delta] = $referenced_item->{$item};
        }
      }
    }

    // Load and add the existing entities.
    if ($ids) {
      foreach (['data_off', 'data_on', 'switcher'] as $item) {
        $target_type = $this->getFieldDefinition()->getSetting('target_type_' . $item);
        $entities = \Drupal::entityTypeManager()->getStorage($target_type)->loadMultiple($ids[$item]);
        foreach ($ids[$item] as $delta => $target_id) {
          if (isset($entities[$target_id])) {
            $target_entities[$delta][$item] = $entities[$target_id];
          }
        }
      }
      // Ensure the returned array is ordered by deltas.
      ksort($target_entities);
    }

    return $target_entities;
  }

  /**
   * {@inheritdoc}
   */
  public static function processDefaultValue($default_value, FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    $default_value = parent::processDefaultValue($default_value, $entity, $definition);

    if ($default_value) {
      // Convert UUIDs to numeric IDs.
      $uuids = [];
      foreach (['data_off', 'data_on', 'switcher'] as $item) {
        foreach ($default_value as $delta => $properties) {
          if (isset($properties[$item . '_uuid'])) {
            $uuids[$item][$delta] = $properties[$item . '_uuid'];
          }
        }
      }
      if ($uuids) {
        $entity_uuids = [];
        foreach (['data_off', 'data_on', 'switcher'] as $item) {
          $target_type = $definition->getSetting('target_type_' . $item);
          $entity_ids = \Drupal::entityQuery($target_type)
            ->condition('uuid', $uuids[$item], 'IN')
            ->execute();
          $entities = \Drupal::entityTypeManager()
            ->getStorage($target_type)
            ->loadMultiple($entity_ids);

          foreach ($entities as $id => $entity) {
            $entity_uuids[$item][$entity->uuid()] = $id;
          }
        }
        foreach (['data_off', 'data_on', 'switcher'] as $item) {
          foreach ($uuids[$item] as $delta => $uuid) {
            if (isset($entity_uuids[$item][$uuid])) {
              $default_value[$delta][$item . '_id'] = $entity_uuids[$item][$uuid];
              unset($default_value[$delta][$item . '_uuid']);
            }
            else {
              unset($default_value[$delta]);
            }
          }
        }
      }

      // Ensure we return consecutive deltas, in case we removed unknown UUIDs.
      $default_value = array_values($default_value);
    }
    return $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    $default_value = parent::defaultValuesFormSubmit($element, $form, $form_state);

    // Convert numeric IDs to UUIDs to ensure config deployability.
    $ids = [];
    foreach ($default_value as $delta => $properties) {
      foreach (['data_off', 'data_on', 'switcher'] as $item) {
        if (isset($properties[$item]) && $properties[$item]->isNew()) {
          // This may be a newly created term.
          $properties[$item]->save();
          $default_value[$delta][$item . '_id'] = $properties[$item]->id();
          unset($default_value[$delta][$item]);
        }
        $ids[$item][] = $default_value[$delta][$item . '_id'];
      }
    }

    foreach (['data_off', 'data_on', 'switcher'] as $item) {
      $entities = \Drupal::entityTypeManager()
        ->getStorage($this->getSetting( 'target_type_' . $item))
        ->loadMultiple($ids[$item]);

      foreach ($default_value as $delta => $properties) {
        unset($default_value[$delta][$item . '_id']);
        $default_value[$delta][$item . '_uuid'] = $entities[$properties[$item . '_id']]->uuid();
      }
    }

    return $default_value;
  }

}
