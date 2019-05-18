<?php

/**
 * @file
 * Contains \Drupal\drd_remote\Form\DrdRemoteSettingsForm.
 */

namespace Drupal\drd_remote\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure drd-remote settings for this site.
 */
class DrdRemoteSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drd_remote_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['drd_remote.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('drd_remote.settings');

    $form = array();

    $form['debug_mode'] = array(
      '#type' => 'checkbox',
      '#title' => t('Debug mode'),
      '#default_value' => $config->get('debug_mode'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('drd_remote.settings');

    $form_state->cleanValues();
    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
