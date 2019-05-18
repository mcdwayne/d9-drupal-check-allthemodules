<?php

/**
 * @file
 * Contains \Drupal\fbip\Form\FbipSettingsForm.
 */

namespace Drupal\fbip\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation;

/**
 * Class FbipSettingsForm.
 *
 * @package Drupal\fbip\Form
 */
class FbipSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'fbip.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fbip_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fbip.settings');
    $form['fbip_all'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Flood Control On All Form Submissions'),
      '#description' => $this->t('Turns on Flood Control on all forms on the site'),
      '#default_value' => $config->get('fbip_all'),
    );
    $form['fbip_form_ids'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Forms to Impose Flood Control on'),
      '#description' => $this->t('List of Form Ids, one in each line, to impose form flood control restrictions on. Current Ip is %ip', array('%ip' => \Drupal::request()->getClientIp())),
      '#default_value' => $config->get('fbip_form_ids'),
      '#states' => array(
        'invisible' => array(
          ':input[name="fbip_all"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['fbip_form_whitelist'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Whitelisted IPs'),
      '#description' => $this->t('List of IPs to whitelist. Whitelisted IPs will bypass '),
      '#default_value' => $config->get('fbip_form_whitelist'),
    );
    $form['fbip_threshold'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Threshold (Number of form submissions'),
      '#description' => $this->t('The maximum number of times each user can open restricted forms per time window defined below'),
      '#default_value' => $config->get('fbip_threshold'),
    );
    $form['fbip_window'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Time Window (In Seconds)'),
      '#description' => $this->t('Number of seconds in the time window for restricting the form generation'),
      '#default_value' => $config->get('fbip_window'),
    );
    $form['fbip_reset'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Reset IP Bans on cron run'),
      '#description' => $this->t('Remove all previous IP bans on cron run. NOTE: This will affect IPs banned NOT just by this module alone but affects all banned IPs'),
      '#default_value' => $config->get('fbip_reset'),
    );

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

    $this->config('fbip.settings')
        ->set('fbip_all', $form_state->getValue('fbip_all'))
        ->set('fbip_form_ids', $form_state->getValue('fbip_form_ids'))
        ->set('fbip_form_whitelist', $form_state->getValue('fbip_form_whitelist'))
        ->set('fbip_threshold', $form_state->getValue('fbip_threshold'))
        ->set('fbip_window', $form_state->getValue('fbip_window'))
        ->set('fbip_reset', $form_state->getValue('fbip_reset'))
        ->save();
  }

}
