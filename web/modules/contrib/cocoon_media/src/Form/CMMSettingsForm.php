<?php

namespace Drupal\cocoon_media\Form;

use Drupal\cocoon_media\CocoonController;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CMMSettingsForm.
 *
 * @package Drupal\cocoon_media\Form
 */
class CMMSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cocoon_media_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('cocoon_media.settings');
    $form['cocoon_media_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Cocoon Media Management Settings'),
      '#description' => $this->t("Register, get your API key, and place it here."),
      '#collapsible' => FALSE,
      '#tree' => TRUE,
    ];
    // CMM Label.
    $form['cocoon_media_settings']['description'] = [
      '#markup' => $this->t("Register, get your API key, and place it here."),
    ];
    // CMM API Key.
    $form['cocoon_media_settings']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cocoon API key'),
      '#default_value' => $config->get('cocoon_media.api_key'),
      '#description' => $this->t('Register on <a target="_blank" href="https://use-cocoon.nl/">use-cocoon.nl</a> and get your API key.'),
    ];
    // CMM domain.
    $form['cocoon_media_settings']['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cocoon Domain'),
      '#default_value' => $config->get('cocoon_media.domain'),
      '#description' => $this->t('Your Cocoon domain (is the first part of the url of your cocoon site)'),
    ];
    // CMM username.
    $form['cocoon_media_settings']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cocoon Username'),
      '#default_value' => $config->get('cocoon_media.username'),
      '#description' => $this->t('Your Cocoon Username'),
    ];
    $form['cocoon_media_settings']['media_image_bundle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Media image bundle'),
      '#default_value' => $config->get('cocoon_media.media_image_bundle'),
      '#description' => $this->t('The machine name of your image bundle, if you are unsure check your <a href="/media/add">/media/add</a> page. This is your media bundle where your imported image is stored.'),
    ];
    $form['cocoon_media_settings']['media_video_bundle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Media video bundle'),
      '#default_value' => $config->get('cocoon_media.media_image_bundle'),
      '#description' => $this->t('The machine name of your image bundle, if you are unsure check your <a href="/media/add">/media/add</a> page. This is your media bundle where your imported video is stored.'),
    ];
    // CMM pagination size.
    $form['cocoon_media_settings']['paging_size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pager size'),
      '#default_value' => $config->get('cocoon_media.paging_size', 15),
      '#description' => $this->t('How many items per page'),
    ];
    // CMM cache duration in seconds.
    $form['cocoon_media_settings']['cache_duration'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cache duration'),
      '#default_value' => $config->get('cocoon_media.cache_duration', 60 * 5),
      '#description' => $this->t('How long cached data will last (in seconds).'),
    ];

    if (
      !empty($config->get('cocoon_media.api_key'))
      && !empty($config->get('cocoon_media.domain'))
      && !empty($config->get('cocoon_media.username'))
    ) {
      $form['cocoon_media_settings']['cocoon_media_test_api'] = [
        '#type' => 'submit',
        '#value' => $this->t('Test API'),
        '#name' => 'testapi',
        '#ajax' => [
          'callback' => [$this, 'ajaxCallbackTestApi'],
          'wrapper' => 'cocoon-output',
          'effect' => 'fade',
        ],
      ];
      $form['cocoon_media_settings']['output'] = [
        '#markup' => '<div id="cocoon-output"></div>',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValue('cocoon_media_settings');
    $config = $this->config('cocoon_media.settings');
    $config->set('cocoon_media.api_key', $form_values['api_key']);
    $config->set('cocoon_media.domain', $form_values['domain']);
    $config->set('cocoon_media.username', $form_values['username']);
    $config->set('cocoon_media.paging_size', $form_values['paging_size']);
    $config->set('cocoon_media.cache_duration', $form_values['cache_duration']);
    $config->set('cocoon_media.media_image_bundle', $form_values['media_image_bundle']);
    $config->set('cocoon_media.media_video_bundle', $form_values['media_video_bundle']);
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * Ajax callback for testing the API.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Renderable array.
   */
  public function ajaxCallbackTestApi(array &$form, FormStateInterface &$form_state) {
    $config = $this->config('cocoon_media.settings');
    $cocoonController = new CocoonController(
      $config->get('cocoon_media.domain'),
      $config->get('cocoon_media.username'),
      $config->get('cocoon_media.api_key'));
    $version = $cocoonController->getVersion();
    $output = '<b>Curren API version is: ' . $version . '</b>';

    return [
      '#markup' => $output,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cocoon_media.settings',
    ];
  }

}
