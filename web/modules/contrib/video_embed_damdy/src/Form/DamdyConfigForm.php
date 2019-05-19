<?php

namespace Drupal\video_embed_damdy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Contact form.
 */
class DamdyConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'video_embed_damdy.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'video_embed_damdy_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('video_embed_damdy.settings');

    $form['damdy_publisher_id'] = [
      '#type' => 'textfield',
      '#title' => t('Damdy Publisher ID'),
      '#default_value' => !empty($config->get('damdy_publisher_id')) ? $config->get('damdy_publisher_id') : '',
      '#required' => FALSE,
    ];

    $form['damdy_guid'] = [
      '#type' => 'textfield',
      '#title' => t('Damdy GUID'),
      '#default_value' => !empty($config->get('damdy_guid')) ? $config->get('damdy_guid') : '',
      '#required' => FALSE,
    ];

    $form['damdy_param_url'] = [
      '#type' => 'textfield',
      '#title' => t('Damdy Param URL'),
      '#default_value' => !empty($config->get('damdy_param_url')) ? $config->get('damdy_param_url') : '',
      '#required' => FALSE,
    ];

    $form['damdy_media_xml_url'] = [
      '#type' => 'textfield',
      '#title' => t('Damdy Mediasxml Url'),
      '#default_value' => !empty($config->get('damdy_media_xml_url')) ? $config->get('damdy_media_xml_url') : '',
      '#required' => FALSE,
    ];

    $form['damdy_player_css'] = [
      '#type' => 'textfield',
      '#title' => t('Damdy Player CSS'),
      '#default_value' => !empty($config->get('damdy_player_css')) ? $config->get('damdy_player_css') : '',
      '#required' => FALSE,
    ];

    $form['damdy_config_js'] = [
      '#type' => 'textfield',
      '#title' => t('Damdy Config JS'),
      '#default_value' => !empty($config->get('damdy_config_js')) ? $config->get('damdy_config_js') : '',
      '#required' => FALSE,
    ];

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
    $this->config('video_embed_damdy.settings')
      ->set('damdy_publisher_id', $form_state->getValue('damdy_publisher_id'))
      ->set('damdy_guid', $form_state->getValue('damdy_guid'))
      ->set('damdy_param_url', $form_state->getValue('damdy_param_url'))
      ->set('damdy_media_xml_url', $form_state->getValue('damdy_media_xml_url'))
      ->set('damdy_player_css', $form_state->getValue('damdy_player_css'))
      ->set('damdy_config_js', $form_state->getValue('damdy_config_js'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
