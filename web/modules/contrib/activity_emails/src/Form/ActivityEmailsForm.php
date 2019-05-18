<?php

namespace Drupal\activity_emails\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ActivityEmailsForm. Contains the admin form.
 *
 * @package Drupal\activity_emails\Form
 */
class ActivityEmailsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'activity_emails.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'activity_emails_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('activity_emails.settings');

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('Enable for emails to be sent.'),
      '#default_value' => $config->get('enabled') ?: FALSE,
    ];

    $form['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('E-mail to notify'),
      '#default_value' => $config->get('email') ?: '',
      '#description' => $this->t('If multiple emails, separate by comma ",".'),
    ];

    $default_template = $this->t('This has been changed:');
    $form['template'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Template'),
      '#description' => $this->t(
        'Template for the email. 
        Entity and user information will be added to the email.
      '),
      '#default_value' => $config->get('template') ?: $default_template,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('activity_emails.settings');
    $config->set('enabled', (bool) $form_state->getValue('enabled'))->save();
    $config->set('email', $form_state->getValue('email'))->save();
    $config->set('template', $form_state->getValue('template'))->save();
  }

}
