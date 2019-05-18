<?php

namespace Drupal\media_entity_flickr\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'flickr_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "flickr_embed",
 *   label = @Translation("Flickr embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class FlickrEmbedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#type' => 'markup',
        '#markup' => $this->getEmbedCode($item),
        '#allowed_tags' => ['img', 'a', 'script'],
      ];
    }
    return $element;
  }

  /**
   * Extracts the raw embed code from input which may or may not be wrapped.
   *
   * @param mixed $value
   *   The input value. Can be a normal string or a value wrapped by the
   *   Typed Data API.
   *
   * @return string|null
   *   The raw embed code.
   */
  protected function getEmbedCode($value) {
    if (is_string($value)) {
      return $value;
    }
    elseif ($value instanceof FieldItemInterface) {
      $class = get_class($value);
      $property = $class::mainPropertyName();
      if ($property) {
        return $value->$property;
      }
    }
  }

}
