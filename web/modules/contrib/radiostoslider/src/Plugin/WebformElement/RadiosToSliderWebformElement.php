<?php

namespace Drupal\radiostoslider\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\OptionsBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'radios_to_slider' element.
 *
 * @WebformElement(
 *   id = "radios_to_slider",
 *   label = @Translation("Radios to Slider"),
 *   description = @Translation("Provides a form element for a set of radio
 *   buttons with radios-to-slider jQuery plugin."),
 *   category = @Translation("Options elements"),
 * )
 */
class RadiosToSliderWebformElement extends OptionsBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      'animation' => TRUE,
      'fit_container' => TRUE,
    ] + parent::getDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['element']['animation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable animation'),
      '#default_value' => $this->getDefaultProperty('animation'),
    ];

    $form['element']['fit_container'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fit container'),
      '#default_value' => $this->getDefaultProperty('fit_container'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(
    array &$element,
    WebformSubmissionInterface $webform_submission = NULL) {

    parent::prepare($element, $webform_submission);

    if (!isset($element['#animation'])) {
      $element['#animation'] = $this->getDefaultProperty('animation');
    }

    if (!isset($element['#fit_container'])) {
      $element['#fit_container'] = $this->getDefaultProperty('fit_container');
    }
  }

}
