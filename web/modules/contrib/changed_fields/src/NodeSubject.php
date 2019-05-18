<?php

/**
 * @file
 * Contains NodeSubject.php.
 */

namespace Drupal\changed_fields;

use Drupal\changed_fields\Plugin\FieldComparator\DefaultFieldComparator;
use Drupal\node\NodeInterface;
use SplObserver;
use SplSubject;

/**
 * Class NodeSubject.
 */
class NodeSubject implements SplSubject {

  /**
   * @var NodeInterface
   */
  private $node;

  /**
   * @var array
   */
  private $changedFields;

  /**
   * @var DefaultFieldComparator
   */
  private $fieldComparatorPlugin;

  /**
   * @var array
   */
  private $observers;

  /**
   * @param NodeInterface $node
   * @param string $fieldComparatorPluginId
   */
  public function __construct(NodeInterface $node, $fieldComparatorPluginId) {
    $this->node = $node;
    $this->changedFields = [];
    $this->fieldComparatorPlugin = \Drupal::service('plugin.manager.changed_fields.field_comparator')->createInstance($fieldComparatorPluginId);
  }

  /**
   * {@inheritdoc}
   */
  public function attach(SplObserver $observer) {
    $this->observers[spl_object_hash($observer)] = $observer;
  }

  /**
   * {@inheritdoc}
   */
  public function detach(SplObserver $observer) {
    unset($this->observers[spl_object_hash($observer)]);
  }

  /**
   * {@inheritdoc}
   */
  public function notify() {
    foreach ($this->observers as $observer) {
      foreach ($observer->getInfo() as $nodeType => $fields) {
        if (!$this->node->isNew() && $this->node->getType() == $nodeType) {
          $changedFields = [];

          foreach ($fields as $fieldName) {
            $oldValue = $this->node->original->get($fieldName)->getValue();
            $newValue = $this->node->get($fieldName)->getValue();
            $fieldDefinition = $this->node->get($fieldName)->getFieldDefinition();
            $result = $this->fieldComparatorPlugin->compareFieldValues($fieldDefinition, $oldValue, $newValue);

            if (is_array($result)) {
              $changedFields[$fieldName] = $result;
            }
          }

          if (!empty($changedFields)) {
            $this->changedFields = $changedFields;
            $observer->update($this);
          }
        }
      }
    }
  }

  /**
   * Returns node object.
   *
   * @return NodeInterface
   */
  public function getNode() {
    return $this->node;
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
