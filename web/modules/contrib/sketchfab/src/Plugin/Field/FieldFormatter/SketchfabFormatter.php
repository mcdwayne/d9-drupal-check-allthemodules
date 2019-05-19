<?php

namespace Drupal\sketchfab\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'sketchfab_field' formatter.
 *
 * @FieldFormatter(
 *   id = "sketchfab_format",
 *   label = @Translation("Iframe"),
 *   module = "sketchfab",
 *   field_types = {
 *     "sketchfab_field"
 *   }
 * )
 */
class SketchfabFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();

    $summary[] = t('Displays the 3D Model from Sketchfab.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      // Render each element as markup.
      $element[$delta] = [
        '#type' => 'inline_template',
        '#template' => '<div class="sketchfab-embed-wrapper"><iframe width="640" height="480" src="{{ url }}/embed" frameborder="0" allowvr allowfullscreen mozallowfullscreen="true" webkitallowfullscreen="true" onmousewheel=""></iframe>',
        '#context' => [
          'url' => $item->value,
        ],
      ];
    }

    return $element;
  }

}
