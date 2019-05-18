<?php

namespace Drupal\insert\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\PerComponentEntityFormDisplay;
use Drupal\migrate\Row;

/**
 * This class imports Insert module field settings of an entity form display.
 * @see \Drupal\migrate\Plugin\migrate\destination\PerComponentEntityFormDisplay
 *
 * @MigrateDestination(
 *   id = "component_entity_form_display_insert"
 * )
 */
class PerComponentEntityFormDisplayInsert extends PerComponentEntityFormDisplay {

  /**
   * @inheritdoc
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $values = [];
    foreach (array_keys($this->getIds()) as $id) {
      $values[$id] = $row->getDestinationProperty($id);
    }
    $entity = $this->getEntity($values['entity_type'], $values['bundle'], $values[static::MODE_NAME]);

    // Add Insert module third party settings to field settings:
    $thirdPartySettings = $row->getDestinationProperty('options/third_party_settings');
    if (count($thirdPartySettings['insert'])) {
      $options = $entity->getComponent($values['field_name']);
      $options['third_party_settings']['insert'] = $thirdPartySettings['insert'];
      $entity->setComponent($values['field_name'], $options);
    }

    $entity->save();
    return array_values($values);
  }

}