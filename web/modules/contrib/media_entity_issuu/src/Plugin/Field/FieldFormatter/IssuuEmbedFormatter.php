<?php

namespace Drupal\media_entity_issuu\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\media_entity_issuu\IssuuMarkup;
use Drupal\media_entity_issuu\Plugin\media\Source\Issuu;

/**
 * Plugin implementation of the 'issuu_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "issuu_embed",
 *   label = @Translation("Issuu embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class IssuuEmbedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\media\MediaInterface $media */
    $media = $items->getEntity();
    $source = $media->getSource();
    $element = [];

    if ($source instanceof Issuu) {
      foreach ($items as $delta => $item) {
        $element[$delta] = [
          '#markup' => IssuuMarkup::create($source->getMetadata($media, 'html')),
        ];
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getTargetEntityTypeId() === 'media';
  }

}
