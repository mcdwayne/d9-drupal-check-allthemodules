<?php

/**
 * @file
 * Contains Drupal\prism\Plugin\Field\FieldWidget\TextPrismWidget.
 */

namespace Drupal\prism\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\prism\PrismConfig;

/**
 * Plugin implementation of the 'text_prism' widget.
 *
 * @FieldWidget(
 *   id = "text_prism",
 *   label = @Translation("Code highlighting"),
 *   field_types = {
 *     "text_long_prism"
 *   }
 * )
 */
class TextPrismWidget extends WidgetBase {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'rows' => '5',
      'placeholder' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['rows'] = array(
      '#type' => 'number',
      '#title' => t('Rows'),
      '#default_value' => $this->getSetting('rows'),
      '#required' => TRUE,
      '#min' => 1,
    );
    $element['placeholder'] = array(
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $summary[] = t('Number of rows: @rows', array('@rows' => $this->getSetting('rows')));
    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = t('Placeholder: @placeholder', array('@placeholder' => $placeholder));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $config = \Drupal::config('prism.settings');
    $all_languages = PrismConfig::getLanguages();
    $config_language = array_filter($config->get('languages'));
    $languages = array_intersect_key($all_languages, $config_language);

    $element['value'] = $element + array(
        '#type' => 'textarea',
        '#default_value' => $items[$delta]->value,
        '#rows' => $this->getSetting('rows'),
        '#placeholder' => $this->getSetting('placeholder'),
        '#attributes' => array('class' => array('js-text-full', 'text-full')),
      );
    $element['languages'] = array(
        '#type' => 'select',
        '#title' => t('Language'),
        '#default_value' => $items[$delta]->languages,
        '#options' => $languages,
        '#description' => t('Select the language to highlight.'),
      );

    return $element;
  }

}
