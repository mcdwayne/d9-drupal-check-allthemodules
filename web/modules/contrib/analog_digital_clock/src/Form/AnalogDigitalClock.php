<?php

namespace Drupal\analog_digital_clock\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Use this class to create configuration form for module.
 */
class AnalogDigitalClock extends ConfigFormBase {

  /**
   * Widget Id.
   */
  public function getFormId() {
    return 'analog_digital_clock_configuration';
  }

  /**
   * Create configurations Name.
   */
  protected function getEditableConfigNames() {
    return [
      'analog_digital_clock.settings',
    ];
  }

  /**
   * Create form for configure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('analog_digital_clock.settings');
    $form['analog_digital_clock_skin'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose clock skin'),
      '#description' => $this->t("To Use Analog Clock: Download library <a href='http://cdnjs.cloudflare.com/ajax/libs/snap.svg/0.2.0/snap.svg-min.js'>snap.svg-min.js</a> and place into libraries/snap.svg/snap.svg-min.js"),
      '#options' => [
        '1' => $this->t('Simple digital clock with date'),
        '2' => $this->t('24 hr Digital clock with date'),
        '3' => $this->t('Analog clock'),
        '4' => $this->t('Animated digital clock'),
      ],
      '#default_value' => $config->get('analog_digital_clock_skin') ? $config->get('analog_digital_clock_skin') : 1,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit password Widget.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('analog_digital_clock.settings')->set('analog_digital_clock_skin', $form_state->getValue('analog_digital_clock_skin'))->save();
    parent::submitForm($form, $form_state);
  }

}
