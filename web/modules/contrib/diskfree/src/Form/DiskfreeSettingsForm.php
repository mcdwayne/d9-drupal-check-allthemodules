<?php

/**
 * @file
 * Contains \Drupal\diskfree\DiskfreeSettingsForm.
 */

namespace Drupal\diskfree\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Form builder for diskfree settings.
 */
class DiskfreeSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'diskfree_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('diskfree.settings');
    $form['diskfree_alert_threshold'] = array(
      '#type' => 'number',
      '#title' => $this->t('Alert threshold percent'),
      '#description' => $this->t('An integer between 0 and 100 which is the minimum disk space to trigger an alert of low disk space. A value of 100 effectively disables alerts.'),
      '#min' => 0,
      '#max' => 100,
      '#default_value' => $config->get('alert_threshold'),
    );
    $form['diskfree_alert_email'] = array(
      '#type' => 'email',
      '#title' => $this->t('Alert e-mail address'),
      '#description' => $this->t('The to e-mail address to send Diskfree low disk space notifications.'),
      '#default_value' => $config->get('alert_email'),
    );
    $form['diskfree_alert_email_freq'] = array(
      '#type' => 'select',
      '#title' => t('Alert e-mail sending frequency'),
      '#description' => $this->t('How often to send e-mail alerts per partition.'),
      '#options' => array(
        '300' => '5 minutes',
        '600' => '10 minutes',
        '900' => '15 minutes',
        '1800' => '30 minutes',
        '3600' => '1 hour',
        '7200' => '2 hours',
        '14400' => '4 hours',
        '28800' => '8 hours',
        '86400' => '1 day',
      ),
      '#default_value' => $config->get('alert_email_freq'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('diskfree.settings')
      ->set('alert_threshold', $form_state['values']['diskfree_alert_threshold'])
      ->set('alert_email', $form_state['values']['diskfree_alert_email'])
      ->set('alert_email_freq', $form_state['values']['diskfree_alert_email_freq'])
      ->save();
    parent::submitForm($form, $form_state);
  }
}
