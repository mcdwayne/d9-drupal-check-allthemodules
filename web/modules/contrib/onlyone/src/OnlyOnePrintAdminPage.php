<?php

namespace Drupal\onlyone;

/**
 * Class OnlyOnePrintAdminPage.
 */
class OnlyOnePrintAdminPage implements OnlyOnePrintStrategyInterface {

  /**
   * {@inheritdoc}
   */
  public function getContentTypesListForPrint(array $content_types) {
    $list = [];
    // Iterating over each content type.
    foreach ($content_types as $content_type => $content_type_info) {
      $cant = count($content_type_info);
      // Iterating over each language.
      for ($i = 0; $i < $cant; $i++) {
        // Example for multilingual sites:
        // Article (En: 7 Nodes, Fr: 5 Nodes, Not specified: 2 Nodes).
        // Example for non multilingual sites:
        // Article (3 Nodes).
        $list[$content_type] = $content_type_info[$i]->name . ' <strong>(' . implode(', ', array_column($content_type_info, 'total_nodes')) . ')</strong>';
      }
    }

    return $list;
  }

}
