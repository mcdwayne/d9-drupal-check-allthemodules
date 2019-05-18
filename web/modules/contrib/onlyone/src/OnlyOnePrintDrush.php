<?php

namespace Drupal\onlyone;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class OnlyOnePrintDrush.
 */
class OnlyOnePrintDrush implements OnlyOnePrintStrategyInterface {

  use StringTranslationTrait;

  /**
   * Green color for text used in drush commands.
   */
  const GREEN_OUTPUT = "\033[1;32;40m\033[1m%s\033[0m";

  /**
   * Red color for text used in drush commands.
   */
  const RED_OUTPUT = "\033[31;40m\033[1m%s\033[0m";

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
        $list[$content_type] = $content_type_info[$i]->name . ' (' . implode(', ', array_column($content_type_info, 'total_nodes')) . ')';
        $list[$content_type] .= $content_type_info[$i]->configured ? ' ' . sprintf(self::GREEN_OUTPUT, $this->t('Configured')) : '';
      }
    }

    return $list;
  }

}
