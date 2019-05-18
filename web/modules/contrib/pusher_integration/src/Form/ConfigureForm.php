<?php

namespace Drupal\pusher_integration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure form.
 */
class ConfigureForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {

    return 'pusher_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {

    return [
      'pusher_integration.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('pusher_integration.settings');

    // Pusher.com Connection Settings.
    $form['pusher'] = array(
      '#type' => 'fieldset',
      '#title' => t('Pusher.com Connection Settings'),
      '#markup' => t('Provide the Pusher.com settings for your particular Pusher.com account/app.'),
    );

    $form['pusher']['pusherAppId'] = array(
      '#type' => 'textfield',
      '#title' => t('Pusher App ID'),
      '#required' => FALSE,
      '#default_value' => $config->get('pusherAppId') ? $config->get('pusherAppId') : '',
      '#description' => t('The Pusher App ID you created at Pusher.com.'),
    );

    $form['pusher']['pusherAppKey'] = array(
      '#type' => 'textfield',
      '#title' => t('Pusher App Key'),
      '#required' => FALSE,
      '#default_value' => $config->get('pusherAppKey') ? $config->get('pusherAppKey') : '',
      '#description' => t('The Pusher App Key you created at Pusher.com.'),
    );

    $form['pusher']['pusherAppSecret'] = array(
      '#type' => 'textfield',
      '#title' => t('Pusher App Secret'),
      '#required' => FALSE,
      '#default_value' => $config->get('pusherAppSecret') ? $config->get('pusherAppSecret') : '',
      '#description' => t('The Pusher App Secret you created at Pusher.com.'),
    );

    $clusters = array(
      'mt1' => 'United States (us-east-1)',
      'eu1' => 'Europe (eu-west-1)',
      'ap1' => 'Asia (asia-southeast-1)',
    );

    $form['pusher']['clusterName'] = array(
      '#type' => 'select',
      '#options' => $clusters,
      '#title' => t('Pusher.com Cluster Name'),
      '#required' => FALSE,
      '#default_value' => $config->get('clusterName') ? $config->get('clusterName') : '',
      '#description' => t('The Pusher.com cluster with which to connect. Note: this *must* match the cluster you chose when creating your app at Pusher.com!'),
    );

    // Channel Configuration.
    // $form['channelConfig'] = array(
    // '#type' => 'fieldset',
    // '#title' => t('Channel Configuration'),
    // '#markup' => t('Create the mapping of Pusher.com channels to your pages. When in doubt, consult the documentation for the module(s) that depend on pusher_integration.'),
    // );
    // $form['channelConfig']['channelPaths'] = array(
    // '#type' => 'textarea',
    // '#title' => t('Channel/Page Mapping'),
    // '#required' => FALSE,
    // '#default_value' => $config->get('channelPaths') ? $config->get('channelPaths') : '',
    // '#description' => t('Matches channels to specific pages (leave blank for all pages). CHANNEL_NAME|ROUTEPATTERN - One entry per line. Regex supported. %TOKEN% can be used to create a unique hash for (for creating private channels).'),
    // '#placeholder' => t("e.g.\nglobal-channel|.*\ntest-channel|/some/path/*\nspecialChannel|partialPathsWorkToo\nprivate-%TOKEN%|/some/path/.*"),
    // );
    // Channel Configuration.
    $form['misc'] = array(
      '#type' => 'fieldset',
      '#title' => t('Miscellaneous Settings'),
    );

    $form['misc']['debugLogging'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable debug logging to Drupal Watchdog'),
      '#required' => FALSE,
      '#default_value' => $config->get('debugLogging'),
      '#description' => t('It goes without saying that this should not be enabled in production environments! But if you need a quick and dirty debug log to watchdog, enable this.'),
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

    $config = $this->config('pusher_integration.settings');

    // General settings.
    $config->set('pusherAppId', $form_state->getValue('pusherAppId'))
            ->set('pusherAppKey', $form_state->getValue('pusherAppKey'))
            ->set('pusherAppSecret', $form_state->getValue('pusherAppSecret'))
            ->set('clusterName', $form_state->getValue('clusterName'))
            // ->set('defaultChannels', $form_state->getValue('defaultChannels'))
            // ->set('channelPaths', $form_state->getValue('channelPaths'))
            ->set('createPrivateChannel', $form_state->getValue('createPrivateChannel'))
            ->set('createPresenceChannel', $form_state->getValue('createPresenceChannel'))
            ->set('presenceChannelName', $form_state->getValue('presenceChannelName'))
            ->set('debugLogging', $form_state->getValue('debugLogging'))
            ->save();

    parent::submitForm($form, $form_state);
  }

}
