<?php

namespace Drupal\private_taxonomy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Implementation of the 'private_taxonomy_term_reference_plain' formatter.
 *
 * @FieldFormatter(
 *   id = "private_taxonomy_term_reference_plain",
 *   label = @Translation("Private plain text"),
 *   field_types = {
 *     "private_taxonomy_term_reference"
 *   }
 * )
 */
class PrivatePlainFormatter extends PrivateTaxonomyFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#plain_text' => $item->entity->label(),
      ];
    }

    return $elements;
  }

}
