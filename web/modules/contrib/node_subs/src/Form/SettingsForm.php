<?php

namespace Drupal\node_subs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'node_subs.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_subs_settings_page_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('node_subs.settings');
    $form['node_subs'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Email settings'),
    ];
    $form['node_subs']['node_subs_count_per_batch'] = [
      '#type' => 'number',
      '#title' => $this->t('Count of mails sent per batch'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('node_subs_count_per_batch'),
    ];
    $form['node_subs']['node_subs_from'] = [
      '#type' => 'email',
      '#title' => $this->t('From address'),
      '#default_value' => $config->get('node_subs_from'),
    ];
    $form['node_subs']['node_subs_is_crone'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send mails by cron'),
      '#description' => $this->t('heck whether you want to send mails on cron'),
      '#default_value' => $config->get('node_subs_is_crone'),
    ];
    $form['node_subs']['node_subs_add_unsubscribe'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add unsubscribe link to mail body'),
      '#description' => $this->t('Check whether you want to add unsubscribe link to mail body (recommended)'),
      '#default_value' => $config->get('node_subs_add_unsubscribe'),
    ];
    $form['node_subs_message'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Message settings'),
    ];
    $form['node_subs_message']['node_subs_allowed_tags'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed tags for email formatting'),
      '#maxlength' => 256,
      '#size' => 128,
      '#default_value' => $config->get('node_subs_allowed_tags'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('node_subs.settings')
      ->set('node_subs_count_per_batch', $form_state->getValue('node_subs_count_per_batch'))
      ->set('node_subs_from', $form_state->getValue('node_subs_from'))
      ->set('node_subs_is_crone', $form_state->getValue('node_subs_is_crone'))
      ->set('node_subs_add_unsubscribe', $form_state->getValue('node_subs_add_unsubscribe'))
      ->set('node_subs_allowed_tags', $form_state->getValue('node_subs_allowed_tags'))
      ->save();
  }

}
