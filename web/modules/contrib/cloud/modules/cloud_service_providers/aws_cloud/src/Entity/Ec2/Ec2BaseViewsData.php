<?php

namespace Drupal\aws_cloud\Entity\Ec2;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides the base views data for Ec2 Entities.
 */
class Ec2BaseViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * Loops through table definitions, adds a select dropdown to certain fields.
   *
   * @param array $data
   *   Data array from getViewsData().
   * @param string $table_name
   *   The entity table name.
   * @param array $fields
   *   A list of fields for a particular entity type.
   * @param array $selectable
   *   An array of selectable fields.
   */
  protected function addDropdownSelector(array &$data, $table_name, array $fields, array $selectable) {
    foreach ($fields as $key => $field) {
      /* @var \Drupal\Core\Field\BaseFieldDefinition $field */
      if (in_array($key, $selectable)) {
        $data[$table_name][$key . '_fs'] = [
          'title' => t('@label (selector)', [
            '@label' => $field->getLabel(),
          ]),
          'help' => t('Provides a dropdown option for text filtering.  If there are no results, the filter will not be shown.'),
          'filter' => [
            'field' => $key,
            'table' => $table_name,
            'id' => 'texttoselect',
            'additional fields' => [],
            'field_name' => $field->getLabel(),
            'allow empty' => TRUE,
          ],
        ];
      }
    }
  }

}
