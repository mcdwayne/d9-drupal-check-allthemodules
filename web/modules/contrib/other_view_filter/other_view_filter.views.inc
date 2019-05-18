<?php

/**
 * @file
 * Views hooks for Other View filter module.
 */

use Drupal\Core\Entity\ContentEntityType;

/**
 * Implements hook_views_data_alter().
 */
function other_view_filter_views_data_alter(array &$data) {
  $definitions = \Drupal::entityTypeManager()->getDefinitions();

  foreach ($definitions as $definition) {
    if ($definition instanceof ContentEntityType) {

      $field = $definition->getKey('id');
      $table = $definition->getDataTable();
      $table = $table ? $table : $definition->getBaseTable();

      if ($field && $table && isset($data[$table])) {
        $data[$table]['other_view_filter'] = [
          'title' => t('Other view result'),
          'help' => t('Filters this view against results of selected other view.'),
          'real field' => $field,
          'filter' => [
            'title' => t('Other view result'),
            'help' => t('Filters this view against results of selected other view.'),
            'field' => $field,
            'id' => 'other_views_filter',
          ],
        ];
      }

    }
  }
}
