<?php

namespace Drupal\snowflakes\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides settings for snowflakes module.
 */
class SnowflakesSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'snowflakes_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['snowflakes.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('snowflakes.settings');

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable snowflakes'),
      '#default_value' => $config->get('enabled'),
      '#description' => t('Uncheck this box to disable snowflakes.'),
    ];

    $form['exclude_admin'] = [
      '#type' => 'checkbox',
      '#title' => t('Exclude admin pages'),
      '#default_value' => $config->get('exclude_admin'),
      '#description' => t('Check this box to disable snowflakes on admin pages.'),
    ];

    $form['toggle_button'] = [
      '#type' => 'checkbox',
      '#title' => t('Show On/Off toggle button'),
      '#default_value' => $config->get('toggle_button'),
      '#description' => t('Show an On/Off button in order to give visitors the chance to deactivate the snowflakes. You can fully control the button via overriding the default CSS rules.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->config('snowflakes.settings');
    $config
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('exclude_admin', $form_state->getValue('exclude_admin'))
      ->set('toggle_button', $form_state->getValue('toggle_button'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
