<?php

namespace Drupal\language_combination\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'language_combination_default' widget.
 *
 * @FieldWidget(
 *   id = "language_combination_default",
 *   label = @Translation("Select list"),
 *   description = @Translation("Default widget for allowing users to define translation combination."),
 *   field_types = {
 *     "language_combination"
 *   }
 * )
 */
class LanguageCombinationWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    if ($form_state->get('list_all_languages')) {
      $languages_options = language_combination_languages_predefined_list();
    }
    else {
      $languages_options = [];
      foreach (\Drupal::languageManager()->getLanguages() as $code => $language) {
        $languages_options[$code] = $language->getName();
      }
    }

    $options = ['_none' => $this->t('- None -')] + $languages_options;
    $element['#type'] = 'fieldset';
    $element['language_source'] = [
      '#type'          => 'select',
      '#title'         => $this->t('From'),
      '#options'       => $options,
      '#default_value' => isset($items[$delta]) ? $items[$delta]->language_source : '',
      '#attributes'    => ['class' => ['language-source']],
    ];

    $element['language_target'] = [
      '#type'          => 'select',
      '#title'         => $this->t('To'),
      '#options'       => $options,
      '#default_value' => isset($items[$delta]) ? $items[$delta]->language_target : '',
      '#attributes'    => ['class' => ['language-target']],
    ];

    return $element;
  }

}
