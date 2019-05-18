<?php

namespace Drupal\media_entity_smugmug\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\media_entity_smugmug\SmugMugMarkup;
use Drupal\media_entity_smugmug\Plugin\media\Source\SmugMug;

/**
 * Plugin implementation of the 'smugmug_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "smugmug_embed",
 *   label = @Translation("SmugMug embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class SmugMugEmbedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\media\MediaInterface $media */
    $source = $media->getSource();
    $element = [];

    if ($source instanceof SmugMug) {
      foreach ($items as $delta => $item) {
        $element[$delta] = [
          '#markup' => SmugMugMarkup::create($source->getMetadata($media, 'html')),
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
