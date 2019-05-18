<?php

namespace Drupal\data\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * A derivative class which provides automatic wizards for all tables.
 *
 * The derivatives store all base table plugin information.
 */
class DataDeriver extends DeriverBase {
  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = array();
    foreach (data_get_all_tables() as $table) {
      $this->derivatives[$table->id()] = array(
        'id' => 'data_table',
        'base_table' => $table->id(),
        'title' => $table->label(),
        'class' => 'Drupal\data\Plugin\views\wizard\DataTable'
      );
    }
    return $this->derivatives;
  }

}
