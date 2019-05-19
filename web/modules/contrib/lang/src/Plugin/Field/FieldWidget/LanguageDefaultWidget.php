<?php

/**
 * @file
 * Definition of Drupal\lang\Plugin\FieldWidget\LanguageDefaultWidget.
 */

namespace Drupal\lang\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;

/**
 * Plugin implementation of the 'language_default' widget.
 *
 * @FieldWidget(
 *   id = "language_default",
 *   label = @Translation("Language select"),
 *   field_types = {
 *     "lang"
 *   }
 * )
 */
class LanguageDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $languages = getLanguageOptions();
    $element['value'] = $element + array(
        '#type' => 'select',
        '#options' => $languages,
        '#empty_value' => '',
        '#default_value' => (isset($items[$delta]->value) && isset($languages[$items[$delta]->value])) ? $items[$delta]->value : NULL,
        '#description' => t('Select a language'),
      );

    return $element;
  }
}
