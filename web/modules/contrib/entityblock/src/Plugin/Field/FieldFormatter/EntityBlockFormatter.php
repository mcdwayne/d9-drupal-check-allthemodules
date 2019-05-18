<?php

/**
 * @file
 * Contains \Drupal\entityblock\Plugin\field\formatter\EntityBlockFormatter.
 */

namespace Drupal\entityblock\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'entityblock' formatter.
 *
 * This formatter renders nothing.
 *
 * @FieldFormatter(
 *   id = "entityblock",
 *   label = @Translation("EntityBlock"),
 *   field_types = {
 *     "entityblock"
 *   }
 * )
 */
class EntityBlockFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // This field should never be rendered.
    return [];
  }
}
