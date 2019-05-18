<?php

namespace Drupal\media_riddle_marketplace\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'riddle_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "riddle_embed",
 *   label = @Translation("Riddle embed"),
 *   field_types = {
 *     "string", "string_long"
 *   }
 * )
 */
class RiddleEmbedFormatter extends FormatterBase {

  /**
   * Extracts the embed code from a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return string|null
   *   The embed code, or NULL if the field type is not supported.
   */
  protected function getEmbedCode(FieldItemInterface $item) {
    switch ($item->getFieldDefinition()->getType()) {

      case 'string':
      case 'string_long':
        return $item->value;

      default:
        break;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {

      if ($code = $this->getEmbedCode($item)) {
        $element[$delta] = [
          '#theme' => 'media_riddle_marketplace',
          '#code' => $code,
          '#attached' => [
            'library' => [
              'riddle_marketplace/riddle.embed',
            ],
          ],
        ];
      }

    }

    return $element;
  }

}
