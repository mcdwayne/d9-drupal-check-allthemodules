<?php

/**
 * @file
 * Contains \Drupal\offline_app\Form\OfflineHomescreenForm;
 */

namespace Drupal\offline_app\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class OfflineHomescreenForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['offline_app.homescreen'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'offline_app_homescreen_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('offline_app.homescreen');

    $form['chrome'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add homescreen tag and manifest for Chrome'),
      '#default_value' => $config->get('chrome'),
    ];

    $form['safari'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add homescreen tag for Safari'),
      '#default_value' => $config->get('safari'),
    ];

    $form['online_pages'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add tags for homescreen support on all "online" pages'),
      '#default_value' => $config->get('online_pages'),
    ];

    $form['offline_pages'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add tags for homescreen support on the "offline" pages'),
      '#default_value' => $config->get('offline_pages'),
    ];

    $form['icon_192'] = [
      '#type' => 'textfield',
      '#title' => $this->t('192x192 launcher icon'),
      '#default_value' => $config->get('icon_192'),
      '#description' => $this->t('Enter the full path to the 192x192 launcher icon.'),
    ];

    $form['icon_192_type'] = [
      '#type' => 'select',
      '#options' => [
        'png' => 'PNG',
        'svg' => 'SVG',
        'gif' => 'GIF',
        'jpg' => 'JPG',
        'jpeg' => 'JPEG',
      ],
      '#title' => $this->t('192x192 launcher icon type'),
      '#default_value' => $config->get('icon_192_type'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('offline_app.homescreen')
      ->set('chrome', $form_state->getValue('chrome'))
      ->set('safari', $form_state->getValue('safari'))
      ->set('online_pages', $form_state->getValue('online_pages'))
      ->set('offline_pages', $form_state->getValue('offline_pages'))
      ->set('icon_192', $form_state->getValue('icon_192'))
      ->set('icon_192_type', $form_state->getValue('icon_192_type'))
      ->save();
    Cache::invalidateTags(['rendered', 'homescreen']);
    parent::submitForm($form, $form_state);
  }

}
