<?php

namespace Drupal\yamaps\Form;

/**
 * @file
 * Contains \Drupal\yamaps\Form\YamapsForm.
 */

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides forms for managing Node Order.
 */
class YamapsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'yamaps_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['yamaps_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key for Yandex.Maps API'),
      '#default_value' => $this->config('yamaps.settings')->get('yamaps_api_key'),
      '#description' => t('Since Yandex changed access to their API we have to send API key. You can get API key %api_link. More info you can find %here', [
        '!api_link' => Link::fromTextAndUrl($this->t('here'), Url::fromUri('https://developer.tech.yandex.ru')),
        '!here' => Link::fromTextAndUrl($this->t('here'), Url::fromUri('https://yandex.ru/blog/mapsapi/novye-pravila-dostupa-k-api-kart')),
      ]),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('yamaps.settings')
      ->set('yamaps_api_key', $form_state->getValue('yamaps_api_key'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['yamaps.settings'];
  }
}
