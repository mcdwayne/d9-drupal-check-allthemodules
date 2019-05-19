<?php

namespace Drupal\usable_json\Form;

/**
 * @file
 * Contains Drupal\usable_json\Form;
 */

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Setting form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return [
      'usable_json.api',
    ];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'usable_json_settings_form';
  }

  /**
   * Build form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Return form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user_ids = \Drupal::entityQuery('user')->condition('status', 1)->execute();
    $users = User::loadMultiple($user_ids);
    $users_options = [];
    foreach ($users as $key => $user) {
      $users_options[$key] = $user->getAccountName();
    }
    $form['api_user'] = [
      '#type' => 'select',
      '#required' => FALSE,
      '#title' => t('API User'),
      '#default_value' => $this->config('usable_json.api')->get('api_user'),
      '#empty_option' => t('- Select -'),
      '#options' => $users_options,
    ];

    $form['api_key'] = [
      '#required' => TRUE,
      '#title' => $this->t('API key'),
      '#type' => 'textfield',
      '#default_value' => $this->config('usable_json.api')->get('api_key'),
    ];

    $form['send_cache_tags'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send cache tags'),
      '#description' => $this->t('Send cache tags trough header for better caching on the client/server side.'),
      '#default_value' => $this->config('usable_json.api')
        ->get('send_cache_tags'),
    ];

    $form['enable_image_styles'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable image styles'),
      '#description' => $this->t('Can effect your rest response time if you have lot of image styles.'),
      '#default_value' => $this->config('usable_json.api')
        ->get('enable_image_styles'),
    ];

    $form['enable_responsive_image_styles'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable responsive image styles'),
      '#description' => $this->t('Will add responsive image styles to your images.'),
      '#default_value' => $this->config('usable_json.api')
        ->get('enable_responsive_image_styles'),
    ];

    $form['generate_random'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate random key'),
      '#submit' => [[$this, 'generateRandom']],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Generate Random.
   *
   * @param array $form
   *   Current form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  public function generateRandom(array &$form, FormStateInterface $form_state) {
    $this->config('usable_json.api')
      ->set('api_key', Crypt::randomBytesBase64())
      ->save();
  }

  /**
   * Submit form.
   *
   * @param array $form
   *   Current form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('usable_json.api')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('api_user', $form_state->getValue('api_user'))
      ->set('send_cache_tags', $form_state->getValue('send_cache_tags'))
      ->set('enable_image_styles', $form_state->getValue('enable_image_styles'))
      ->set('enable_responsive_image_styles', $form_state->getValue('enable_responsive_image_styles'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
