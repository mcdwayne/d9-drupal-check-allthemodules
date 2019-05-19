<?php

namespace Drupal\trending_images\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TrendingImagesConfigForm.
 * @package Drupal\trending_images\Form
 */
class TrendingImagesConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'trending_images_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'trending_images.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config(reset($this->getEditableConfigNames()));
    $trendingImagesService = \Drupal::service('trending_images.service');
    $host = \Drupal::request()->getSchemeAndHttpHost();
    $enabledProviders = $trendingImagesService->getEnabledTrendingImagesPlugins();
    foreach ($enabledProviders as $channel){
      $apiKey = $config->get($channel.'_api_key');
      $apiSecret = $config->get($channel.'_api_secret');

      $form[$channel] = [
        '#type' => 'details',
        '#title' => $channel,
        '#description' => 'Both, API Key and API Secret need to be set, in order to be able to request Authentication token for your social network account.',
        '#attached' => [
          'library' => ['trending_images/oauth-authorization'],
          'drupalSettings' => ['custom_trending_images' => [
            'bodyClass' => 'trending_images_authenticated',
          ]],
        ],
      ];

      $form[$channel][$channel.'_api_key'] = [
        '#title' => t('API Key:'),
        '#type' => 'textfield',
        '#default_value' => $apiKey
      ];

      $form[$channel][$channel.'_api_secret'] = [
        '#title' => t('API Secret:'),
        '#type' => 'textfield',
        '#default_value' => $apiSecret
      ];

      if(!empty($apiKey) && !empty($apiSecret) && empty($config->get('instagram_authentication_token'))){
        $form[$channel][$channel.'_label'] = [
          '#type' => 'label',
          '#title' => 'Authentication token for "'.$channel.'" is not set',
        ];
        $form[$channel][$channel.'_connect'] = [
          '#type' => 'link',
          '#title' => t('Get Authentication token'),
          '#url' => \Drupal\Core\Url::fromUri('https://api.instagram.com/oauth/authorize/?client_id='.$apiKey.'&redirect_uri='.$host.'/trending-images/'.$channel.'/authentication&response_type=code', array()),
          '#attributes' => [
            'target' => '_blank',
            'class' => ['btn', 'custom-'.$channel.'-oauth-authorization'],
          ],
        ];
      }elseif(!empty($apiKey) && !empty($apiSecret) && !empty($config->get('instagram_authentication_token'))){
        $form[$channel][$channel.'_label'] = [
          '#type' => 'label',
          '#title' => t('Authentication token is set'),
        ];

        $form[$channel]['actions']['submit'] = [
          '#type' => 'submit',
          '#button_type' => 'primary',
          '#value' => $this->t('Remove authentication token'),
          '#ajax' => [
            'callback' => [$this, 'trending_images_trending_images_form_deauthenticate_ajax'],
          ],
        ];
      }
      $form['actions']['process_trending_images'] = [
        '#submit' => [[$this, 'processTrendingImages']],
        '#type' => 'submit',
        '#value' => $this->t('Process trending images'),
        '#button_type' => 'default',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form,  $form_state);
    $trendingImagesConfig = \Drupal::configFactory()->getEditable('trending_images.config');
    $trendingImagesService = \Drupal::service('trending_images.service');
    $enabledProviders = $trendingImagesService->getEnabledTrendingImagesPlugins();
    foreach ($enabledProviders as $channel){
      $trendingImagesConfig
        ->set($channel.'_api_key',$form_state->getValue($channel.'_api_key'))
        ->set($channel.'_api_secret',$form_state->getValue($channel.'_api_secret'))
        ->save();
    }
  }

  /**
   * Bug fix on the token deauth
   * Submit handler to authentication token removal account.
   */
  public function trending_images_trending_images_form_deauthenticate_ajax($form, \Drupal\Core\Form\FormStateInterface &$form_state) {
    $response = new \Drupal\Core\Ajax\AjaxResponse();
    $configName = reset($this->getEditableConfigNames());
    $config = \Drupal::configFactory()->getEditable($configName);
    $config->set('instagram_authentication_token', NULL)->save();

    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand(NULL,'reloadPage'));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   */
  public function processTrendingImages(array &$form, FormStateInterface $form_state) {
    \Drupal::service('trending_images.service')->processTrendingFields();
    drupal_set_message(t('Trending images updated.'));
  }
}

