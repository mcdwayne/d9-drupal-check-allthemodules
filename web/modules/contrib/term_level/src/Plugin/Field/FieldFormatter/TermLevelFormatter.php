<?php

namespace Drupal\term_level\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\term_level\Plugin\Field\FieldType\TermLevelItem;

/**
 * Plugin for Term level formatter.
 *
 * @FieldFormatter(
 *   id = "term_level_formatter",
 *   label = @Translation("Term level formatter"),
 *   field_types = {
 *     "term_level"
 *   }
 * )
 */
class TermLevelFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    $values = $items->getValue();
    $field_settings = $this->getFieldSettings();
    $levels = TermLevelItem::extractLevels($field_settings['levels']);
    foreach ($elements as $delta => $entity) {
      $level = $values[$delta]['level'];
      $elements[$delta]['level'] = [
        '#markup' => ' : ' . $levels[$level],
      ];
    }
    return $elements;
  }

}
