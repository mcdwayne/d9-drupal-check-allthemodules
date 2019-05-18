<?php

namespace Drupal\radiostoslider\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'radiostoslider' widget.
 *
 * @FieldWidget(
 *   id = "radiostoslider",
 *   label = @Translation("Radios to Slider"),
 *   field_types = {
 *     "boolean",
 *     "entity_reference",
 *     "list_integer",
 *     "list_float",
 *     "list_string",
 *   }
 * )
 */
class RadiosToSliderWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'animation' => TRUE,
      'fit_container' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['animation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable animation'),
      '#default_value' => $this->getSetting('animation'),
    ];
    $form['fit_container'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fit container'),
      '#default_value' => $this->getSetting('fit_container'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t(
      'Animation enabled: @animation',
      ['@animation' => $this->getSetting('animation') == 1 ? 'Yes' : 'No']
    );
    $summary[] = $this->t(
      'Fit container: @fit_container',
      [
        '@fit_container' => $this->getSetting('fit_container') == 1 ?
        'Yes' :
        'No',
      ]
    );

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state) {

    $element = parent::formElement(
      $items,
      $delta,
      $element,
      $form,
      $form_state
    );

    $options = $this->getOptions($items->getEntity());
    $selected = $this->getSelectedOptions($items);

    // If required and there is one single option, preselect it.
    if ($this->required && count($options) == 1) {
      reset($options);
      $selected = [key($options)];
    }

    $element += [
      '#type' => 'radios_to_slider',
      // Radio buttons need a scalar value. Take the first default value, or
      // default to NULL so that the form element is properly recognized as
      // not having a default value.
      '#default_value' => $selected ? reset($selected) : NULL,
      '#options' => $options,
      '#animation' => $this->getSetting('animation'),
      '#fit_container' => $this->getSetting('fit_container'),
    ];

    $element['#attributes']['class'][] = 'radiostoslider-wrapper';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(
    array $element,
    FormStateInterface $form_state) {

    if ($element['#required'] && $element['#value'] == '_none') {
      $form_state->setError(
        $element,
        $this->t('@name field is required.', ['@name' => $element['#title']])
      );
    }

    $value = $element['#value'] == '_none' ? NULL : $element['#value'];
    $items = [$element['#key_column'] => $value];
    $form_state->setValueForElement($element, $items);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    if (!$this->required && !$this->multiple) {
      return $this->t('N/A');
    }
  }

}
