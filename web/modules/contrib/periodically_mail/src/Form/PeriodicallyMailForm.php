<?php

namespace Drupal\periodically_mail\Form;

use Drupal\user\Entity\Role;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Class SettingsForm.
 *
 * @package Drupal\periodically_mail\Form
 */
class PeriodicallyMailForm extends ConfigFormBase {

  /**
   * Get form id.
   */
  public function getFormId() {
    return 'periodically_mail';
  }

  /**
   * Get form names.
   */
  protected function getEditableConfigNames() {
    return [
      'periodically_mail.settings',
    ];
  }

  /**
   * Build form fields.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('periodically_mail.settings');
    $roles = Role::loadMultiple();
    foreach ($roles as $key => $role) {
      $roles[$key] = $role->get('label');
    }
    $form['periodically_mail_user_type'] = [
      '#type' => 'select',
      '#title' => t('User Role to send Email'),
      '#default_value' => $config->get('user_type'),
      '#options' => $roles,
      '#required' => TRUE,
    ];
    $periods[24 * 60 * 60] = t('Daily');
    $periods[7 * 24 * 60 * 60] = t('Weekly');
    $periods[2 * 7 * 24 * 60 * 60] = t('2 Weeks');
    $periods[4 * 7 * 24 * 60 * 60] = t('4 Weeks');
    $form['periodically_mail_periode'] = [
      '#type' => 'select',
      '#title' => t('Period TIMESTAMP'),
      '#default_value' => $config->get('periode'),
      '#options' => $periods,
      '#required' => TRUE,
    ];

    $form['periodically_mail_started_from'] = [
      '#type' => 'datetime',
      '#title' => t('Started from Timestamp'),
      '#default_value' => $config->get('started_from') ? DrupalDateTime::createFromTimestamp($config->get('started_from')) : DrupalDateTime::createFromTimestamp(time()),
      '#required' => TRUE,
    ];

    $form['periodically_mail_email_title'] = [
      '#type' => 'textfield',
      '#title' => t('Email Title'),
      '#default_value' => $config->get('email_title'),
      '#required' => TRUE,
    ];

    $form['periodically_mail_email'] = [
      '#type' => 'text_format',
      '#title' => t('Email'),
      '#format' => $config->get('email')['format'],
      '#default_value' => $config->get('email')['value'],
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    \Drupal::configFactory()->getEditable('periodically_mail.settings')
      ->set('user_type', $form_state->getValue('periodically_mail_user_type'))
      ->set('periode', intval($form_state->getValue('periodically_mail_periode')))
      ->set('started_from', strtotime($form_state->getValue('periodically_mail_started_from')))
      ->set('email_title', $form_state->getValue('periodically_mail_email_title'))
      ->set('email', $form_state->getValue('periodically_mail_email'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Validate Form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (intval($form_state->getValue('periodically_mail_periode')) <= 0) {
      $form_state->setErrorByName('periodically_mail_periode', $this->t('<em>Period TIMESTAMP</em> must be a positive value'));
    }
    if (strtotime($form_state->getValue('periodically_mail_started_from')) <= time()) {
      $form_state->setErrorByName('periodically_mail_started_from', $this->t('<em>Started from Timestamp</em> must be a future time'));
    }
  }

}
