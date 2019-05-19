<?php

namespace Drupal\vimeo_upload\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class VimeoUploadAdminSettingsForm.
 */
class VimeoUploadSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'vimeo_upload.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'video_upload_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vimeo_upload.settings');

    $vimeo_api_url = Url::fromUri('https://developer.vimeo.com/apps', ['attributes' => ['target' => '_blank']]);
    $vimeo_api_link = Link::fromTextAndUrl(t('Vimeo developer dashboard'), $vimeo_api_url)->toString();
    $form['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access token'),
      '#description' => $this->t('Create an access token via your @api_link with the following scope: public private edit upload.', ['@api_link' => $vimeo_api_link]),
      '#maxlength' => 32,
      '#size' => 32,
      '#required' => TRUE,
    // @todo decrypt
      '#default_value' => $config->get('access_token'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('vimeo_upload.settings')
    // @todo encrypt
      ->set('access_token', $form_state->getValue('access_token'))
      ->save();
  }

}
