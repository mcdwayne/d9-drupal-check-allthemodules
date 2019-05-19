<?php

/**
 * @file
 * Contains \Drupal\skype\Plugin\Field\FieldWidget\SkypeDefaultWidget.
 */

namespace Drupal\skype\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'skype_default' widget.
 *
 * @FieldWidget(
 *   id = "skype_default",
 *   module = "skype",
 *   label = @Translation("Skype ID"),
 *   field_types = {
 *     "skype"
 *   }
 * )
 */
class SkypeDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#attributes' => ['class' => ['edit-skype-fields-skype-id']],
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#prefix' => '<div class="skype-fields-skype-id-field">',
      '#suffix' => '</div>',
    ];
    return $element;
  }

}
