<?php

namespace Drupal\c2e\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the configuration form.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'c2e_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'c2e.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('c2e.settings');

    $form['cron'] = [
      '#title' => $this->t('Process by cron'),
      '#type' => 'checkbox',
      '#description' => $this->t('With this checkbox you can disable automatic e-mail processing by cron.'),
      '#default_value' => $config->get('cron'),
    ];

    $form['batch_size'] = [
      '#title' => $this->t('Batch size'),
      '#type' => 'number',
      '#description' => $this->t('How many messages to process on each invocation.'),
      '#required' => TRUE,
      '#maxlength' => 3,
      '#min' => 1,
      '#default_value' => $config->get('batch_size'),
    ];

    $form['duplicate'] = [
      '#title' => $this->t('Avoid duplications'),
      '#type' => 'checkbox',
      '#description' => $this->t('This allows you to reduce the number of repetitive e-mails.'),
      '#default_value' => $config->get('duplicate'),
    ];

    $form['create_users'] = [
      '#title' => $this->t('Create users by incoming e-mails'),
      '#type' => 'checkbox',
      '#description' => $this->t('Create user accounts based on incoming e-mails.'),
      '#default_value' => $config->get('create_users'),
    ];

    $form['delete_collect'] = [
      '#title' => $this->t('Delete processed data'),
      '#type' => 'checkbox',
      '#description' => $this->t('Remove the processed e-mails from the collect table.'),
      '#default_value' => $config->get('delete_collect'),
    ];

    $form['header_to'] = [
      '#title' => $this->t('Unknown recipient'),
      '#type' => 'email',
      '#description' => $this->t('If the recipient can not detected, this value will be used.'),
      '#default_value' => $config->get('header_to'),
    ];

    $form['header_from'] = [
      '#title' => $this->t('Unknown sender'),
      '#type' => 'email',
      '#description' => $this->t('If the sender can not detected, this value will be used.'),
      '#default_value' => $config->get('header_from'),
    ];

    $form['header_subject'] = [
      '#title' => $this->t('Unknown or empty subject'),
      '#type' => 'textfield',
      '#description' => $this->t('If the subject can not detected, this value will be used.'),
      '#default_value' => $config->get('header_subject'),
    ];

    $form['prefix'] = [
      '#title' => $this->t('Identifier prefix'),
      '#type' => 'textfield',
      '#size' => 4,
      '#maxlength' => 3,
      '#description' => $this->t('Email identifier prefix'),
      '#default_value' => $config->get('prefix'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->configFactory->getEditable('c2e.settings')
      ->set('cron', $form_state->getValue('cron'))
      ->set('batch_size', $form_state->getValue('batch_size'))
      ->set('duplicate', $form_state->getValue('duplicate'))
      ->set('create_users', $form_state->getValue('create_users'))
      ->set('delete_collect', $form_state->getValue('delete_collect'))
      ->set('header_to', $form_state->getValue('header_to'))
      ->set('header_from', $form_state->getValue('header_from'))
      ->set('header_subject', $form_state->getValue('header_subject'))
      ->set('prefix', $form_state->getValue('prefix'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
