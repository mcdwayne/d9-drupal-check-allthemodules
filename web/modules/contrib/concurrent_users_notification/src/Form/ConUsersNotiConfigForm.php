<?php

namespace Drupal\concurrent_users_notification\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConUsersNotiConfigForm.
 *
 * @package Drupal\concurrent_users_notification\Form
 */
class ConUsersNotiConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'concurrent_users_notification.conusersnoticonfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'con_users_noti_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $keys = range(10, 50000, 10);
    $period = array_combine($keys, $keys);
    $cron_url = \Drupal::url('concurrent_users_notification.saverecord', ['key' => \Drupal::state()->get('concurrent_users_notification.cun_key'), ['absolute' => TRUE]]);
    $history_url = \Drupal::url('concurrent_users_notification.history');
    $config = $this->config('concurrent_users_notification.conusersnoticonfig');
    $mailing_list = $this->config('concurrent_users_notification.conusersnoticonfig')->get('email_id');
    $subject = $this->config('concurrent_users_notification.conusersnoticonfig')->get('subject');
    $mail_body = $this->config('concurrent_users_notification.conusersnoticonfig')->get('message');
    if (!$mailing_list) {
      $mailing_list = $this->config('system.site')->get('mail');
    }

    $form['concurrent_critical_users_count'] = [
      '#type' => 'select',
      '#title' => $this->t('Concurrent users count'),
      '#description' => $this->t('Critical level to trigger notification mail'),
      '#options' => $period,
      '#size' => 1,
      '#default_value' => $config->get('concurrent_critical_users_count'),
    ];
    $form['enable_notification_mail'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Notification mail'),
      '#description' => $this->t('A mail will be triggered to given mail id if Concurrent user login count reached to limit.'),
      '#default_value' => $config->get('enable_notification_mail'),
    ];
    $form['email_id'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Id'),
      '#description' => $this->t('Notification mail will be triggered on this email.'),
      '#default_value' => $mailing_list,
      '#states' => array(
        'invisible' => array(
          ':input[name="enable_notification_mail"]' => array('checked' => FALSE),
        ),
      ),
    ];
    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#description' => $this->t('Subject for this email.'),
      '#default_value' => ($subject) ?: 'Alert : concurrent users reached : ',
      '#states' => array(
        'invisible' => array(
          ':input[name="enable_notification_mail"]' => array('checked' => FALSE),
        ),
      ),
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message Body'),
      '#description' => $this->t('Notification mail body.'),
      '#default_value' => ($mail_body) ?: "Concurrent users count reached on criticle level.",
      '#states' => array(
        'invisible' => array(
          ':input[name="enable_notification_mail"]' => array('checked' => FALSE),
        ),
      ),
    ];
    $form['viewhistory_url'] = [
      '#prefix' => '<br />',
      '#markup' => t('To view history of concurrent login user count, go to <a href=":history_url">View History</a>', [':history_url' => $history_url]),
    ];

    $form['cron_url'] = [
      '#prefix' => '<br />',
      '#markup' => t('Please set a cron job on your server with required frequency to send notification mail on critical level and save the history. URL :  <a href=":url">@cron</a>', [':url' => $cron_url, '@cron' => $cron_url]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!valid_email_address($form_state->getValue('email_id'))) {
      $form_state->setErrorByName('email_id', t('That e-mail address is not valid.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('concurrent_users_notification.conusersnoticonfig')
        ->set('enable_notification_mail', $form_state->getValue('enable_notification_mail'))
        ->set('email_id', $form_state->getValue('email_id'))
        ->set('subject', $form_state->getValue('subject'))
        ->set('message', $form_state->getValue('message'))
        ->set('concurrent_critical_users_count', $form_state->getValue('concurrent_critical_users_count'))
        ->save();
  }

}
