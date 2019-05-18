<?php

/**
 * @file
 * Contains \Drupal\field_template\Plugin\Field\FieldFormatter\FieldTemplateFormatter.
 */

namespace Drupal\field_template\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'text_default' formatter.
 *
 * @FieldFormatter(
 *   id = "field_template",
 *   label = @Translation("Field Template"),
 *   field_types = {
 *   },
 *   quickedit = {
 *     "editor" = "plain_text"
 *   }
 * )
 */
class FieldTemplateFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#theme' => 'field_template',
        '#item' => $item,
        '#field' => $this->fieldDefinition,
      );
    }

    return $elements;
  }

}
