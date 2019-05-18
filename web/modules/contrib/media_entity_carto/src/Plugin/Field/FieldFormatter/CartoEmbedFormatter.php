<?php

namespace Drupal\media_entity_carto\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\media_entity_carto\Plugin\media\Source\Carto;

/**
 * Plugin implementation of the 'carto_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "carto_embed",
 *   label = @Translation("CARTO embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class CartoEmbedFormatter extends FormatterBase {

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
      case 'link':
        return $item->uri;

      case 'string':
      case 'string_long':
        return $item->value;

      default:
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {
      $matches = [];

      foreach (Carto::$validationRegexp as $pattern => $key) {
        if (preg_match($pattern, $this->getEmbedCode($item), $item_matches)) {
          $matches[] = $item_matches;
        }
      }

      if (!empty($matches)) {
        $matches = reset($matches);
      }

      if (!empty($matches['user']) && !empty($matches['id'])) {
        $element[$delta] = [
          '#markup' => '<iframe width="100%" height="520" frameborder="0" src="https://' . $matches['user'] . '.carto.com/builder/' . $matches['id'] . '/embed" allowfullscreen webkitallowfullscreen mozallowfullscreen oallowfullscreen msallowfullscreen></iframe>',
          '#allowed_tags' => ['iframe'],
        ];
      }
    }

    return $element;
  }

}
