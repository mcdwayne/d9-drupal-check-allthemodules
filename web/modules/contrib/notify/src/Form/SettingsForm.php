<?php

namespace Drupal\notify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

if (!defined('NOTIFY_NODE_TYPE')) {
  define('NOTIFY_NODE_TYPE', 'notify_node_type_');
}

/**
 * Defines a form that configures forms module settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'notify_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'notify.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('notify.settings');

    $period = array(
      300 => \Drupal::service('date.formatter')->formatInterval(300),
      600 => \Drupal::service('date.formatter')->formatInterval(600),
      900 => \Drupal::service('date.formatter')->formatInterval(900),
      1800 => \Drupal::service('date.formatter')->formatInterval(1800),
      3600 => \Drupal::service('date.formatter')->formatInterval(3600),
      10800 => \Drupal::service('date.formatter')->formatInterval(10800),
      21600 => \Drupal::service('date.formatter')->formatInterval(21600),
      43200 => \Drupal::service('date.formatter')->formatInterval(43200),
      86400 => \Drupal::service('date.formatter')->formatInterval(86400),
      172800 => \Drupal::service('date.formatter')->formatInterval(172800),
      259200 => \Drupal::service('date.formatter')->formatInterval(259200),
      604800 => \Drupal::service('date.formatter')->formatInterval(604800),
      1209600 => \Drupal::service('date.formatter')->formatInterval(1209600),
      2419200 => \Drupal::service('date.formatter')->formatInterval(2419200),
      -1 => t('Never'),
    );

    $attempts = array(
      0 => t('Disabled'),
      1 => 1,
      2 => 2,
      3 => 3,
      4 => 4,
      5 => 5,
      6 => 6,
      7 => 7,
      8 => 8,
      9 => 9,
      10 => 10,
      15 => 15,
      20 => 20,
    );

    $batch = array(
      2 => 2,
      3 => 3,
      10 => 10,
      20 => 20,
      50 => 50,
      100 => 100,
      200 => 200,
      400 => 400,
    );

    $form['notify_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('E-mail notification settings'),
      '#collapsible' => TRUE,
    );

    $form['notify_settings']['notify_period'] = array(
      '#type' => 'select',
      '#title' => t('Send notifications every'),
      '#default_value' => $config->get('notify_period', array(86400)),
      '#options' => $period,
      '#description' => t('How often should new content notifications be sent? Requires cron to be running at least this often.'),
    );

    $form['notify_settings']['notify_send_hour'] = array(
      '#type' => 'select',
      '#title' => t('Hour to Send Notifications'),
      '#description' => t('Specify the hour (24-hour clock) in which notifications should be sent, if the frequency is one day or greater.'),
      '#default_value' => $config->get('notify_send_hour', 9),
      '#options' => array(
        0, 1, 2, 3, 4, 5, 6, 7, 8, 9,
        10, 11, 12, 13, 14, 15, 16, 17, 18, 19,
        20, 21, 22, 23,
      ),
    );

    $form['notify_settings']['notify_attempts'] = array(
      '#type' => 'select',
      '#title' => t('Number of failed sends after which notifications are disabled'),
      '#default_value' => $config->get('notify_attempts', array(5)),
      '#options' => $attempts,
    );

    $form['notify_settings']['notify_batch'] = array(
      '#type' => 'select',
      '#title' => t('Maximum number of notifications to send out per cron run'),
      '#description' => t('The maximum number of notification e-mails to send in each pass of  a cron maintenance task. If necessary, reduce the number of items to prevent resource limit conflicts.'),
      '#default_value' => $config->get('notify_batch', array(100)),
      '#options' => $batch,
    );

    $form['notify_settings']['notify_include_updates'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include updated posts in notifications'),
      '#return_value' => 1,
      '#default_value' => $config->get('notify_include_updates', 1),
    );

    $form['notify_settings']['notify_unpublished'] = array(
      '#type' => 'checkbox',
      '#title' => t('Administrators shall be notified about unpublished content of tracked types'),
      '#return_value' => 1,
      '#default_value' => $config->get('notify_unpublished', 1),
    );

    $form['notify_settings']['notify_watchdog'] = array(
      '#type' => 'radios',
      '#title' => t('Watchdog log level'),
      '#default_value' => $config->get('notify_watchdog', array(3)),
      '#options' => array(t('All'), t('Failures+Summary'), t('Failures'), t('Nothing')),
      '#description' => t('This setting lets you specify how much to log.'),
    );

    $form['notify_settings']['notify_weightur'] = array(
      '#type' => 'textfield',
      '#title' => t('Weight of notification field in user registration form'),
      '#default_value' => $config->get('notify_weightur', 0),
      '#size' => 3,
      '#maxlength' => 5,
      '#description' => t('The weight you set here will determine the position of the notification field when it appears in the user registration form.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('notify.settings')
      ->set('notify_period', $values['notify_period'])
      ->set('notify_send_hour', $values['notify_send_hour'])
      ->set('notify_attempts', $values['notify_attempts'])
      ->set('notify_batch', $values['notify_batch'])
      ->set('notify_include_updates', $values['notify_include_updates'])
      ->set('notify_unpublished', $values['notify_unpublished'])
      ->set('notify_watchdog', $values['notify_watchdog'])
      ->set('notify_weightur', $values['notify_weightur'])
      ->save();
    drupal_set_message(t('Notify admin settings saved.'));
  }

}
