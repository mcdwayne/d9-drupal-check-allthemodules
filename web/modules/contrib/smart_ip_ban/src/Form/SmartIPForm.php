<?php

namespace Drupal\smart_ip_ban\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config;

class SmartIPForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smart_ip_ban_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $threshold = \Drupal::state()->get('smart_ip_ban_threshold');
    $interval = \Drupal::state()->get('smart_ip_ban_interval');
    $duration = \Drupal::state()->get('smart_ip_ban_duration');
    $excluded_ip = \Drupal::state()->get('smart_ip_ban_excluded_ip');
    $options_interval = array();
    for($i = 1; $i < 12; $i++) {
      $options_interval[$i*5] = t(":i minutes", array(':i' => $i * 5));
    }
    $options_duration = array();
    for($i = 1; $i < 36; $i++) {
      $options_duration[$i*10] = t(":i minutes", array(':i' => $i * 10));
    }
    $form['smart_ip_ban_threshold'] = array(
    '#title' => t('No. of failed attempts to exclude'),
    '#type' => 'select',
    '#required' => TRUE,
    '#options' => range(1, 25),
    '#default_value' => $threshold,
    );
    $form['smart_ip_ban_interval'] = array(
      '#title' => t('Check failed attempts reported in last'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => $options_interval,
      '#default_value' => $interval,
    );
    $form['smart_ip_ban_duration'] = array(
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => t('How long to ban the IP?'),
      '#options' => $options_duration,
      '#default_value' => $duration,
    );
    $form['smart_ip_ban_excluded_ip'] = array(
      '#type' => 'textarea',
      '#title' => t('IP addresses to exclude, if any'),
      '#description' => t('Enter one IP per line.'),
      '#default_value' => $excluded_ip,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Submit',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $exclude_ip = $form_state->getValue('smart_ip_ban_excluded_ip');
    if (!empty($exclude_ip)) {
      $ips = explode("\n", $exclude_ip);
      foreach ($ips as $ip) {
        $ip = trim($ip);
        if (!empty($ip) && !filter_var($ip, FILTER_VALIDATE_IP)) {
          $form_state->setErrorByName('smart_ip_ban_excluded_ip', $this->t('IP @ip is invalid.', array('@ip' => $ip)));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::state()->set('smart_ip_ban_threshold', $form_state->getValue('smart_ip_ban_threshold'));
    \Drupal::state()->set('smart_ip_ban_interval', $form_state->getValue('smart_ip_ban_interval'));
    \Drupal::state()->set('smart_ip_ban_duration', $form_state->getValue('smart_ip_ban_duration'));
    \Drupal::state()->set('smart_ip_ban_excluded_ip', $form_state->getValue('smart_ip_ban_excluded_ip'));
    $config = \Drupal::configFactory()->getEditable('user.flood');
    $config->set('user_limit', \Drupal::state()->get('smart_ip_ban_threshold'));
    $config->set('user_window', \Drupal::state()->get('smart_ip_ban_duration')*60);
    $config->set('ip_window', \Drupal::state()->get('smart_ip_ban_duration')*60);
    $config->save(TRUE);
    drupal_set_message(t('Your configurations is saved.'), 'status');
  }
}
