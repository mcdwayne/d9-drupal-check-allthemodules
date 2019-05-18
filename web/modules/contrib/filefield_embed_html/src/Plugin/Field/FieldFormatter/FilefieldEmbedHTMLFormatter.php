<?php

namespace Drupal\filefield_embed_html\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\DescriptionAwareFileFormatterBase;

/**
 * Plugin implementation of the 'filefield_embed_html_iframe' formatter.
 *
 * @FieldFormatter(
 *   id = "filefield_embed_html_iframe",
 *   label = @Translation("Filefield Embed HTML"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FilefieldEmbedHTMLFormatter extends DescriptionAwareFileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Embed HTML in an iframe.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $source = file_create_url(BASE_URI . $file->id()) . '/index.html';
      $iframe_id = 'filefield-embed-html-iframe-'. $file->id();

      $elements[$delta] = [
        '#markup' => '<iframe id="'. $iframe_id .'" scrolling="no" frameBorder="0" width="100%" src="'. $source .'"></iframe>',
        '#allowed_tags' => ['iframe'],
        '#cache' => [
          'tags' => $file->getCacheTags(),
        ],
        '#attached' => [
          'library' => [
            'filefield_embed_html/iframe-resizer.init'
          ]
        ]
      ];
    }

    return $elements;
  }

}