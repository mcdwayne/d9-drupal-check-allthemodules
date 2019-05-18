<?php

namespace Drupal\iots_measure\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class IotsMeasureSettingsForm.
 *
 * @ingroup iots_measure
 */
class IotsMeasureSettingsForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'iotsmeasure_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['iotsmeasure.settings'];
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('iotsmeasure.settings');
    $config
      ->set("cron", $form_state->getValue("cron"))
      ->save();
  }

  /**
   * Defines the settings form for Iots Measure entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('iotsmeasure.settings');
    $form["general"] = [
      '#type' => 'details',
      '#title' => $this->t('General Settings'),
      '#open' => TRUE,
    ];
    $form["general"]["cron"] = [
      '#title' => $this->t("Enable Cron"),
      '#type' => 'checkbox',
      '#default_value' => $config->get("cron"),
    ];
    return parent::buildForm($form, $form_state);
  }

}
