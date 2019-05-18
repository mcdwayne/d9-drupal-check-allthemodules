<?php

/**
 * @file
 * Contains \Drupal\form_protect\Form\Settings.
 */

namespace Drupal\form_protect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_protect_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['form_protect.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('form_protect.settings');

    $form['form_ids'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Forms IDs'),
      '#description' => $this->t('The IDs of forms to be protected. One per line.'),
      '#default_value' => implode("\n", $config->get('form_ids')),
    );
    $form['log'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Log blocked submits'),
      '#default_value' => $config->get('log'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Store form IDs as array.
    $form_ids = $form_state->getValue('form_ids', '');
    $form_ids = array_filter(explode("\n", str_replace("\r", "\n", $form_ids)));
    $form_state->setValue('form_ids', $form_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('form_protect.settings')
      ->set('form_ids', $form_state->getValue('form_ids'))
      ->set('log', $form_state->getValue('log'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}