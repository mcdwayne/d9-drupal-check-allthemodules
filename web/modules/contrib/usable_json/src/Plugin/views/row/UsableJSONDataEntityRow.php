<?php

namespace Drupal\usable_json\Plugin\views\row;

use Drupal\rest\Plugin\views\row\DataEntityRow;

/**
 * Plugin which displays entities as raw data.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "usable_json_data_entity",
 *   title = @Translation("Usable JSON Entity"),
 *   help = @Translation("Use entities as row data."),
 *   display_types = {"data"}
 * )
 */
class UsableJSONDataEntityRow extends DataEntityRow {

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    return $this->getEntityTranslation($row->_entity, $row);
  }

}
