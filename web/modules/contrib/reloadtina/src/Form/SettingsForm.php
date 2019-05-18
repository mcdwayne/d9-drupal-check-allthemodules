<?php

namespace Drupal\reloadtina\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures reloadtina settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reloadtina_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'reloadtina.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('reloadtina.settings');

    $form['multipliers'] = array(
      '#type' => 'textfield',
      '#title' => t('Multipliers'),
      '#default_value' => $config->get('multipliers'),
      '#description' => t('Enter which multipliers to support, separated by space. E.g. <em>1.5 2</em>'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $multipliers = $this->processMultiplierSubmission($values['multipliers']);
    foreach ($multipliers as $multiplier) {
      $multiplier = trim($multiplier);
      if (!is_numeric($multiplier)) {
        $form_state->setError($form['multipliers'], t('Invalid multiplier: %multiplier', ['%multiplier' => $multiplier]));
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $multipliers = $this->processMultiplierSubmission($values['multipliers'], TRUE);
    $this->config('reloadtina.settings')
      ->set('multipliers', $multipliers)
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Convert multipliers from string to array.
   */
  protected function processMultiplierSubmission($multipliers, $cast = FALSE) {
    $multipliers = explode(' ', $multipliers);
    foreach ($multipliers as &$multiplier) {
      $multiplier = $cast ? (float) trim($multiplier) : trim($multiplier);
    }
    return $multipliers;
  }
}
