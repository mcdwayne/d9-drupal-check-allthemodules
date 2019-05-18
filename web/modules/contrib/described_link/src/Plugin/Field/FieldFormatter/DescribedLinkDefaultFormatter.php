<?php

namespace Drupal\described_link\Plugin\Field\FieldFormatter;

use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * @FieldFormatter(
 *   id = "described_link_default",
 *   label = @Translation("Described link"),
 *   description = @Translation("Display the link with description."),
 *   field_types = {
 *     "described_link"
 *   }
 * )
 */
class DescribedLinkDefaultFormatter extends LinkFormatter {

  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $values = $items->getValue();

    foreach ($elements as $delta => $entity) {
      unset($elements[$delta]['#type']);
      $elements[$delta]['#theme'] = 'described_link';
      $elements[$delta]['#description'] = $values[$delta]['description'];
    }

    return $elements;
  }
}