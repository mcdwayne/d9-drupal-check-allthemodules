<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc6;

use Drupal\node\Plugin\migrate\source\d6\ViewMode as NodeViewMode;

/**
 * The view mode source.
 *
 * A copy of the base class including adding the type_name field to the query.
 * The type name is used in the prepareRow event handler to determine the
 * destination entity type.
 *
 * @MigrateSource(
 *   id = "uc6_view_mode",
 *   source_module = "content"
 * )
 */
class ViewMode extends NodeViewMode {

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    // Copy the iterator from node/src/Plugin/migrate/uc6/ViewMode so that the
    // type_name can be added to the row and used in the prepareRow event.
    $rows = [];
    $result = $this->prepareQuery()->execute();
    while ($field_row = $result->fetchAssoc()) {
      $field_row['display_settings'] = unserialize($field_row['display_settings']);
      foreach ($this->getViewModes() as $view_mode) {
        // Append to the return value if the row has display settings for this
        // view mode and the view mode is neither hidden nor excluded.
        // @see \Drupal\field\Plugin\migrate\source\uc6\FieldInstancePerViewMode::initializeIterator()
        if (isset($field_row['display_settings'][$view_mode]) && $field_row['display_settings'][$view_mode]['format'] != 'hidden' && empty($field_row['display_settings'][$view_mode]['exclude'])) {
          if (!isset($rows[$view_mode])) {
            $rows[$view_mode]['entity_type'] = 'node';
            $rows[$view_mode]['view_mode'] = $view_mode;
            $rows[$view_mode]['type_name'] = $field_row['type_name'];
          }
        }
      }
    }

    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    $query->addField('cnfi', 'type_name');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'display_settings' => $this->t('Serialize data with display settings.'),
      'type_name' => $this->t('The field bundle'),
    ];
  }

}
