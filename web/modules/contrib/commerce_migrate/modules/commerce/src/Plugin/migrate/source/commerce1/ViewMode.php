<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1;

use Drupal\field\Plugin\migrate\source\d7\ViewMode as CoreViewMode;

/**
 * The view mode source class.
 *
 * Gets all the view modes taking into account that nodes can be product
 * displays.
 *
 * @MigrateSource(
 *   id = "commerce1_view_mode",
 *   source_module = "field"
 * )
 */
class ViewMode extends CoreViewMode {

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $instances = parent::initializeIterator();

    $rows = [];
    foreach ($instances->getArrayCopy() as $instance) {
      $data = unserialize($instance['data']);
      $instance['commerce1_entity_type'] = $instance['entity_type'];
      foreach (array_keys($data['display']) as $view_mode) {
        $key = $instance['entity_type'] . '.' . $view_mode;
        $rows[$key] = array_merge($instance, [
          'view_mode' => $view_mode,
        ]);
        // If this is a node view mode, then it is also a product display view
        // mode. Create a new row for product display.
        if ($instance['entity_type'] === 'node') {
          $new_row = $instance;
          $key = 'product_display.' . $view_mode;
          // The entity_type is a sourceId and this entity_type does not exist
          // in the source database. By adding it here, it allows for the
          // creation of two rows without using entity_generate which, when
          // used, means that rollbacks will not work correctly because the
          // generated entity is not in the map table.
          $new_row['entity_type'] = 'product_display';
          $new_row['commerce1_entity_type'] = 'product_display';
          $rows[$key] = array_merge($new_row, [
            'view_mode' => $view_mode,
          ]);
        }
      }
    }
    return new \ArrayIterator($rows);
  }

}
