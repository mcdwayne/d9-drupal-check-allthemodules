<?php

/**
 * @file
 * Contains \Drupal\environment\Form\EnvironmentSwitchConfirm.
 */

namespace Drupal\environment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class EnvironmentSwitchConfirm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'environment_switch_confirm';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    if (empty($environment)) {
      drupal_set_message(t('Invalid environment "%environment". You cannot switch to an undefined environment.', [
        '%environment' => $environment
        ]), 'warning');
      drupal_goto('admin/settings/environment');
    }
    return confirm_form([
      'environment' => [
        '#type' => 'hidden',
        '#value' => $environment,
      ]
      ], t('Are you sure you want to switch the current environment?'), 'admin/settings/environment', t('This action switches the current environment to "%env". This kind of change is as risky as updating your site. This action cannot be undone.', [
      '%env' => $environment
      ]), t('Switch environment'), t('Cancel'));
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (isset($form['environment'])) {
      foreach ($form['environment'] as $workflow => $environment) {
        if ($form_state->getValue(['environment', $workflow]) != $form['environment'][$workflow]) {
          environment_switch($form_state->getValue(['environment', $workflow]), $workflow);
        }
      }
    }
    $form_state->set(['redirect'], 'admin/settings/environment');
  }

}
