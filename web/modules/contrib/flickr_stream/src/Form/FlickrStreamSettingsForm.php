<?php

namespace Drupal\flickr_stream\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Flickr Stream settings for this site.
 */
class FlickrStreamSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flickr_stream_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'flickr_stream.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('flickr_stream.settings');

    $form['flickr_stream_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Flickr Api Key'),
      '#description' => $this->t('Enter the flickr app api key(to get them go <a href="https://www.flickr.com/services/apps">Get api key</a>)'),
      '#default_value' => $config->get('flickr_stream_api_key'),
    ];

    $form['flickr_stream_photo_count'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default photos count to greb'),
      '#description' => $this->t('Enter the count photo to get by default.'),
      '#default_value' => $config->get('flickr_stream_photo_count'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('flickr_stream.settings')
      ->set('flickr_stream_api_key', $form_state->getValue('flickr_stream_api_key'))
      ->set('flickr_stream_photo_count', $form_state->getValue('flickr_stream_photo_count'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
