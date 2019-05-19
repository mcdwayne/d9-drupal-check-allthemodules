<?php

namespace Drupal\video_embed_vkontakte\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\video_embed_vkontakte\Form
 */
class SettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'video_embed_vkontakte_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'video_embed_vkontakte.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('video_embed_vkontakte.settings');

    $form['access_token'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Access token'),
      '#default_value' => $config->get('access_token'),
      '#description' => $this->t('You can get this <b>Access token</b> of Vkontakte by this <a href="@doc">documentation</a>.', array('@doc' => 'https://vk.com/dev/PHP_SDK')),
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->configFactory->getEditable('video_embed_vkontakte.settings');
    $settings->set('access_token', $form_state->getValue('access_token'));
    $settings->save();

    parent::submitForm($form, $form_state);
  }

}
