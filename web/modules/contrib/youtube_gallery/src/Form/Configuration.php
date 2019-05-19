<?php

namespace Drupal\youtube_gallery\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure youtube_gallery settings for this site.
 */
class Configuration extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'youtube_gallery_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'youtube_gallery.formsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('youtube_gallery.formsettings');

    $form['panel'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Youtube Authentication Configuration'),
      '#open' => TRUE,
    ];

    $form['panel']['get_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter API Key'),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
    ];

    $form['panel']['get_channel_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter Youtube Channel Id'),
      '#default_value' => $config->get('channel_id'),
      '#required' => TRUE,
    ];

    $form['panel']['max_videos'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number Of Videos'),
      '#default_value' => $config->get('max_videos'),
      '#required' => TRUE,
    ];

    $form['panel2'] = [
      '#type' => 'details',
      '#title' => $this->t('Client OAuth Configuration'),
      '#open' => TRUE,
    ];

    $form['panel2']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client OAuth Id'),
      '#default_value' => $config->get('client_id'),
    ];

    $form['panel2']['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client OAuth Secret'),
      '#default_value' => $config->get('client_secret'),
    ];

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if (!is_numeric($form_state->getValue('max_videos'))) {

      $form_state->setErrorByName('max_videos', $this->t('Only contain numeric values.'));
    }
    if (substr($form_state->getValue('get_channel_id'), 0, 2) !== "UC") {

      $form_state->setErrorByName('get_channel_id', $this->t('Not a valid channel Id'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('youtube_gallery.formsettings')
      ->set('api_key', $form_state->getValue('get_api_key'))
      ->set('max_videos', $form_state->getValue('max_videos'))
      ->set('channel_id', $form_state->getValue('get_channel_id'))
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
