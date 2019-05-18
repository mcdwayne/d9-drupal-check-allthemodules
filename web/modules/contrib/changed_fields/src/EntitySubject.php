<?php

namespace Drupal\changed_fields;

use Drupal\changed_fields\Plugin\FieldComparator\DefaultFieldComparator;
use Drupal\Core\Entity\ContentEntityInterface;
use InvalidArgumentException;
use SplObserver;
use SplSubject;

/**
 * Class EntitySubject.
 */
class EntitySubject implements SplSubject {

  /**
   * @var ContentEntityInterface
   */
  protected $entity;

  /**
   * @var array
   */
  protected $changedFields;

  /**
   * @var DefaultFieldComparator
   */
  protected $fieldComparatorPlugin;

  /**
   * @var array
   */
  protected $observers;

  /**
   * @param ContentEntityInterface $entity
   * @param string $field_comparator_plugin_id
   */
  public function __construct(ContentEntityInterface $entity, $field_comparator_plugin_id = 'default_field_comparator') {
    $this->entity = $entity;
    $this->changedFields = [];
    $this->fieldComparatorPlugin = \Drupal::service('plugin.manager.changed_fields.field_comparator')->createInstance($field_comparator_plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function attach(SplObserver $observer) {
    if (!($observer instanceof ObserverInterface)) {
      throw new InvalidArgumentException('Observer must implement ObserverInterface interface.');
    }

    $this->observers[spl_object_hash($observer)] = $observer;
  }

  /**
   * {@inheritdoc}
   */
  public function detach(SplObserver $observer) {
    if (!($observer instanceof ObserverInterface)) {
      throw new InvalidArgumentException('Observer must implement ObserverInterface interface.');
    }

    unset($this->observers[spl_object_hash($observer)]);
  }

  /**
   * {@inheritdoc}
   */
  public function notify() {
    if ($this->entity->isNew()) {
      return;
    }

    foreach ($this->observers as $observer) {
      foreach ($observer->getInfo() as $entity_type => $entity_bundles) {
        if ($this->entity->getEntityTypeId() != $entity_type) {
          continue;
        }

        foreach ($entity_bundles as $bundle => $fields) {
          if ($this->entity->bundle() != $bundle) {
            continue;
          }

          $changed_fields = [];

          foreach ($fields as $field_name) {
            // TODO: what if observer subscribed to un-existing fields?
            $old_value = $this->entity->original->get($field_name)->getValue();
            $new_value = $this->entity->get($field_name)->getValue();
            $field_definition = $this->entity->get($field_name)->getFieldDefinition();
            $result = $this->fieldComparatorPlugin->compareFieldValues($field_definition, $old_value, $new_value);

            if (is_array($result)) {
              $changed_fields[$field_name] = $result;
            }
          }

          if (!empty($changed_fields)) {
            $this->changedFields = $changed_fields;
            $observer->update($this);
          }
        }
      }
    }
  }

  /**
   * Returns entity object.
   *
   * @return ContentEntityInterface
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Returns changed fields.
   *
   * @return array
   */
  public function getChangedFields() {
    return $this->changedFields;
  }

}
