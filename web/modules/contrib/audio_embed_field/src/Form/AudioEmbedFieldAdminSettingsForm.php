<?php

namespace Drupal\audio_embed_field\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure audio_embed_field settings for this site.
 */
class AudioEmbedFieldAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'audio_embed_field_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['audio_embed_field.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('audio_embed_field.settings');

    $form['soundcloud'] = [
      '#type' => 'details',
      '#title' => $this->t('Soundcloud settings'),
      '#open' => TRUE,
    ];

    $form['soundcloud']['soundcloud_id'] = [
      '#default_value' => $config->get('soundcloud_id'),
      '#description' => $this->t('This is your application\'s SoundCloud ID as used by their API. Find your apps at <a target="_blank" href="http://soundcloud.com/you/apps">http://soundcloud.com/you/apps</a>'),
      '#maxlength' => 60,
      '#size' => 255,
      '#title' => $this->t('Soundcloud Client ID'),
      '#type' => 'textfield',
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
    $config = $this->config('audio_embed_field.settings');
    $config
      ->set('soundcloud_id', $form_state->getValue('soundcloud_id'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
