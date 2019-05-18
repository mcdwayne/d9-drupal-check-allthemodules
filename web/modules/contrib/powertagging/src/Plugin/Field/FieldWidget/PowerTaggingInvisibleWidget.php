<?php
/**
 * @file
 * Contains \Drupal\powertagging\Plugin\Field\FieldWidget\PowerTaggingInvisibleWidget
 */

namespace Drupal\powertagging\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'powertagging_invisible' widget.
 *
 * @FieldWidget(
 *   id = "powertagging_invisible",
 *   label = @Translation("Invisible Footprint"),
 *   field_types = {
 *     "powertagging_tags"
 *   },
 *   multiple_values = TRUE
 * )
 */
class PowerTaggingInvisibleWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Attach the libraries.
    $element['#attached'] = [
      'library' => [
        'powertagging/widget_css_only',
      ],
    ];

    return $element;
  }

}