<?php

namespace Drupal\media_entity_imgur\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\media_entity\EmbedCodeValueTrait;
use Drupal\media_entity_imgur\Plugin\MediaEntity\Type\Imgur;

/**
 * Plugin implementation of the 'imgur_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "imgur_embed",
 *   label = @Translation("Imgur embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class ImgurEmbedFormatter extends FormatterBase {

  use EmbedCodeValueTrait;

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();
    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#type' => 'markup',
        '#markup' => $this->getEmbedCode($item),
        '#allowed_tags' => ['blockquote', 'a', 'script'],
      ];
    }
    return $element;
  }

}
