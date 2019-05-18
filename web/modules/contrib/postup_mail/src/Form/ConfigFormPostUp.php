<?php

namespace Drupal\postup_mail\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ConfigFormPostUp extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'postup_mail_config_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('postup_mail.settings');

    $form['url'] = array(
      '#type' => 'url',
      '#title' => $this->t('Url for API'),
      '#default_value' => $config->get('url'),
      '#required' => TRUE,
    );

    $form['login'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Login'),
      '#default_value' => $config->get('login'),
      '#required' => TRUE,
    );

    $form['password'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $config->get('password'),
      '#required' => TRUE,
    );

    $form['prefix'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Prefix'),
      '#default_value' => $config->get('prefix'),
      '#descriptiom' => $this->t('Prefix for the external id'),
    );

    $form['template_id'] = array(
      '#type' => 'number',
      '#title' => $this->t('Template id'),
      '#default_value' => $config->get('template_id'),
      '#required' => TRUE,
    );

    $form['message_success_text'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Message success text'),
      '#default_value' => $config->get('message_success_text'),
      '#required' => TRUE,
    );

    $form['message_error_text'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Message error text'),
      '#default_value' => $config->get('message_error_text'),
      '#required' => TRUE,
    );

    $form['logging'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Logging'),
      '#default_value' => $config->get('logging'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('postup_mail.settings');
    $config
      ->set('url', $form_state->getValue('url'))
      ->set('login', $form_state->getValue('login'))
      ->set('password', $form_state->getValue('password'))
      ->set('prefix', $form_state->getValue('prefix'))
      ->set('template_id', $form_state->getValue('template_id'))
      ->set('logging', $form_state->getValue('logging'))
      ->set('message_error_text', $form_state->getValue('message_error_text'))
      ->set('message_success_text', $form_state->getValue('message_success_text'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['postup_mail.settings'];
  }

}
