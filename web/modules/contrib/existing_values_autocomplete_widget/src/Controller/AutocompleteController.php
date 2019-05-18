<?php

namespace Drupal\existing_values_autocomplete_widget\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;

/**
 * Class AutocompleteController.
 */
class AutocompleteController extends ControllerBase {
  /**
   * Handleautocomplete.
   *
   * @return string
   *   Return Hello string.
   */
  public function handleAutocomplete(Request $request, $field_name = NULL, $count = 15) {
    $existing_values = [];

    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = Unicode::strtolower(array_pop($typed_string));

      $table_mapping = \Drupal::entityTypeManager()->getStorage('node')->getTableMapping();
      $field_table = $table_mapping->getFieldTableName($field_name);
      $field_storage_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('node')[$field_name];
      $field_column = $table_mapping->getFieldColumnName($field_storage_definitions, 'value');

      $query = \Drupal::database()->select($field_table, 'f');
      $query->fields('f', array($field_column));
      $query->condition($field_column, $query->escapeLike($typed_string) . '%', 'LIKE');
      $query->distinct(TRUE);
      $results = $query->execute()->fetchCol();

        foreach ($results as $value) {
          $existing_values[] = [
            'value' => $value,
            'label' => $value,
          ];
        }
    }

    return new JsonResponse($existing_values);
  }

}
