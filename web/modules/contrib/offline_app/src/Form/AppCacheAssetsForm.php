<?php

/**
 * @file
 * Contains \Drupal\offline_app\Form\AppCacheAssetsForm;
 */

namespace Drupal\offline_app\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class AppCacheAssetsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['offline_app.appcache'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'offline_app_appcache_assets_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('offline_app.appcache');

    $form['stylesheets'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Stylesheets'),
      '#default_value' => $config->get('stylesheets'),
      '#description' => $this->t('Enter full paths to stylesheets. "/offline/css-from-default-theme.css" is the stylesheet of the aggregated CSS of your theme. Remove if you do not want to use this.'),
    ];

    $form['javascript'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Javascript'),
      '#default_value' => $config->get('javascript'),
      '#description' => $this->t('Enter full paths to javascript.'),
    ];

    $form['assets_folder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Assets folder'),
      '#default_value' => $config->get('assets_folder'),
      '#description' => $this->t('Enter the path to an assets-folder that needs to be cached for offline use. Note that all these assets will be cached, so only add those background-images, fonts etc. that are really needed.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('offline_app.appcache')
      ->set('stylesheets', $form_state->getValue('stylesheets'))
      ->set('javascript', $form_state->getValue('javascript'))
      ->set('assets_folder', $form_state->getValue('assets_folder'))
      ->save();
    Cache::invalidateTags(['appcache.manifest', 'appcache']);
    parent::submitForm($form, $form_state);
  }

}
