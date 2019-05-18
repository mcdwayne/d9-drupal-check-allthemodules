<?php

/**
 * @file
 * Contains \Drupal\mixpanel\Form\MixpanelSettingsForm.
 */

namespace Drupal\mixpanel\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure mixpanel settings for this site.
 */
class MixpanelSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mixpanel_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mixpanel.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mixpanel.settings');

    $form['mixpanel_token'] = array(
      '#title' => t('Mixpanel Token'),
      '#type' => 'textfield',
      '#default_value' => $config->get('mixpanel_token'),
      '#description' => t('The token you got from mixpanel.com for this domain.'),
    );

    $form['javascript_library_version'] = array(
      '#title' => t('Mixpanel Javascript library version'),
      '#type' => 'radios',
      '#options' => array(
        '1.0' => t("1.0 - <em>Don't use unless you have legacy code which depends on 1.0!</em>"),
        '2.0' => '2.0',
      ),
      '#default_value' => $config->get('javascript_library_version'),
      '#description' => t('In April 2012, Mixpanel updated their Javascript library to version 2.0. It adds several new features but unfortunately has a completely new API. Unless you have legacy code which depends on 1.0, you should be using 2.0!'),
    );

    $form['use_queue'] = array(
      '#title' => t('Queue and send events during cron'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('use_queue'),
      '#description' => t('Enabling this will increase site performance but delay sending data to Mixpanel until cron is run.'),
    );

    $form['use_cross_subdomain_cookie'] = array(
      '#title' => t('Set Mixpanel cookie across all subdomains'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('use_cross_subdomain_cookie'),
      '#description' => t('Enabling this use the same Mixpanel cookie for <em>site1</em>.example.com and <em>site2</em>.example.com. <strong>Only works with version 2.0 of the Javascript library!</strong>'),
    );

    $form['track_anonymous'] = array(
      '#title' => t('Track anonymous users'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('track_anonymous'),
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    );

    $form['#theme'] = 'system_config_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('mixpanel.settings')
      ->set('mixpanel_token', $form_state->getValue('mixpanel_token'))
      ->set('javascript_library_version', $form_state->getValue('javascript_library_version'))
      ->set('use_queue', $form_state->getValue('use_queue'))
      ->set('use_cross_subdomain_cookie', $form_state->getValue('use_cross_subdomain_cookie'))
      ->set('track_anonymous', $form_state->getValue('track_anonymous'))
      ->save();

    drupal_set_message(t('Your changes have been saved.'), 'status');
    // @todo Decouple from form: http://drupal.org/node/2040135.
    Cache::invalidateTags(array('config' => 'mixpanel.settings'));

  }

}
