<?php

namespace Drupal\stats\Plugin\StatSource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\stats\Annotation\StatSource;
use Drupal\stats\Plugin\StatSourceBase;
use Drupal\stats\StatExecution;
use Drupal\stats\Row;
use Drupal\stats\RowCollection;

/**
 * @StatSource(
 *   id = "entity_query",
 *   label = "Entity Query"
 * )
 */
class EntityQuery extends StatSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getRows(): RowCollection {
    $query = \Drupal::entityQuery($this->configuration['entity_type']);

    // Provide static conditions.
    if (!empty($this->configuration['conditions'])) {
      foreach ($this->configuration['conditions'] as $condition) {
        $query->condition($condition['field'], $condition['value'], $condition['operator']);
      }
    }

    // Fire conditions by the value of given trigger.
    if (!empty($this->configuration['trigger_conditions'])) {
      $trigger = $this->statExecution->getTriggerEntity();
      foreach ($this->configuration['trigger_conditions'] as $condition) {
        $prop = $this->getProperty($trigger, $condition['value']);
        $query->condition($condition['field'], $prop, $condition['operator']);
      }
    }

    $ids = $query->execute();

    // Load retrieved entities.
    // @todo: replace with lazyloading concept, some that maybe only passes raw
    // data to the row and has a plugin specific callback for that data or maybe
    // using typed data API with EntityAdapter is an option here too.
    $storage = \Drupal::entityTypeManager()->getStorage($this->configuration['entity_type']);

    $entities = $storage->loadMultiple($ids);

    $collection = new RowCollection();
    foreach ($entities as $entity) {
      // Init empty row.
      $row = new Row([]);
      $this->stuffRow($row, $trigger, $entity);
      $collection->addRow($row);
    }

    return $collection;
  }

  /**
   * Sets the row values to the specified versions.
   *
   * @param \Drupal\stats\Row $row
   * @param \Drupal\Core\Entity\EntityInterface $trigger
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @throws \Exception
   */
  protected function stuffRow(Row $row, EntityInterface $trigger, EntityInterface $entity) {
    foreach ($this->configuration['properties'] as $prop => $spec) {
      // A simple string as specification falls back to a entity selector.
      if (!is_array($spec)) {
        $spec = ['selector' => $spec];
      }

      // When source is set to trigger, the selector is applied to the trigger
      // entity.
      if (!empty($spec['trigger'])) {
        $val = $this->getProperty($trigger, $spec['selector']);
      }
      // .. otherwise the entity from the stat will be used.
      else {
        $val = $this->getProperty($entity, $spec['selector']);
      }

      $row->setSourceProperty($prop, $val);
    }
  }
}
