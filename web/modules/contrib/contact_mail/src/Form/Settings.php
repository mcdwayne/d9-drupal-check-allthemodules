<?php

namespace Drupal\contact_mail\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the form settings controller.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contact_mail_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['contact_mail.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('contact_mail.settings');

    $form['contact'] = [
      '#type' => 'details',
      '#title' => $this->t('Contact Form'),
      '#open' => TRUE,
    ];
    $form['contact']['emails'] = [
      '#title' => $this->t('Contact form Recipients'),
      '#default_value' => $config->get('emails'),
      '#description' => $this->t('1 email per line.'),
      '#type' => 'textarea',
    ];
    $form["contact"]['tpl'] = [
      '#title' => $this->t('Rewrite submission template'),
      '#description' => $this->t('Module template looks good.'),
      '#type' => 'checkbox',
      '#maxlength' => 20,
      '#required' => FALSE,
      '#size' => 15,
      '#default_value' => $config->get('tpl'),
    ];
    $form["contact"]['html'] = [
      '#title' => $this->t('Send html instead txt'),
      '#type' => 'checkbox',
      '#maxlength' => 20,
      '#required' => FALSE,
      '#size' => 15,
      '#default_value' => $config->get('html'),
      '#description' => $this->t('Add text/html header.'),
    ];
    $form["contact"]['header'] = [
      '#title' => $this->t('Mail extra information'),
      '#default_value' => $config->get('header'),
      '#description' => $this->t('Message before submission.'),
      '#type' => 'textarea',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('contact_mail.settings');
    $config
      ->set('emails', $form_state->getValue('emails'))
      ->set('tpl', $form_state->getValue('tpl'))
      ->set('html', $form_state->getValue('html'))
      ->set('header', $form_state->getValue('header'))
      ->save();
  }

}
