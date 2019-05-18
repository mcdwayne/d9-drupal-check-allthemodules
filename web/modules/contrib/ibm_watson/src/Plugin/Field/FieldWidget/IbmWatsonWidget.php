<?php

namespace Drupal\ibm_watson\Plugin\Field\FieldWidget;

use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'ibm_watson' widget.
 *
 * @FieldWidget(
 *   id = "ibm_watson",
 *   module = "ibm_watson",
 *   label = @Translation("IBM Watson view"),
 *   field_types = {
 *     "ibm_watson"
 *   },
 *   settings = {
 *     "placeholder_url" = ""
 *   }
 * )
 */
class IbmWatsonWidget extends FileWidget {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['field_widget_display']['#access'] = FALSE;
    $elements['field_widget_display_settings']['#access'] = FALSE;
    /*
    $elements['placeholder_url'] = array(
    '#type' => 'textfield',
    '#title' => t('Placeholder for URL'),
    '#default_value' => $this->getSetting('placeholder_url'),
    '#description' => t('Text'),
    );
     */
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = parent::settingsSummary();

    return $summary;
  }

  /**
   * Form API callback: Processes a ibm_watson field element.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $item = $element['#value'];

    // Add the additional alt and title fields.
    $element['language'] = [
      '#title' => t('Language'),
      '#type' => 'select',
      '#default_value' => isset($item['language']) ? $item['language'] : '',
      '#description' => t('The identifier of the model to be used for the recognition request.'),
      // @see https://www.drupal.org/node/465106#alt-text
      '#maxlength' => 512,
      '#weight' => -12,
      '#required' => TRUE,
      '#options' => ibm_watson_model(),
    ];
    return parent::process($element, $form_state, $form);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // $field_settings = $this->getFieldSettings();
    return $element;
  }

  /**
   * Validate input.
   */
  public function validateInput(&$element, FormStateInterface &$form_state, $form) {

    // $input = $element['#value'];.
  }

}
