<?php

namespace Drupal\cloudfront_edge_caching\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Invalidate manual URL.
 */
class CecAdminInvalidateUrl extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cec_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cec.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Textarea.
    $form['url'] = [
      '#type' => 'textarea',
      '#title' => $this->t('URL to invalidate'),
      '#description' => $this->t('Specify the existing path you wish to invalidate. For example: /node/28, /forum/1. Enter one value per line'),
      '#required' => TRUE,
    ];

    $form['invalidate'] = [
      '#type' => 'submit',
      '#value' => $this->t('Invalidate URL'),
      '#submit' => ['::invalidateSubmission'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Get the URL.
    $url_value = explode("\n", $form_state->getValue('url'));

    if (!empty($url_value) && is_array($url_value) && count($url_value) > 0) {
      foreach ($url_value as $value) {
        if (substr($value, 0, 1) != '/' && !empty($value)) {
          $form_state->setErrorByName('url', $this->t('The URL introduced is not valid.'));
        }
      }
    }

    else {
      $form_state->setErrorByName('url', $this->t('The URL introduced is not valid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateSubmission(array &$form, FormStateInterface $form_state) {
    // Get the URL.
    $url_value = explode("\n", $form_state->getValue('url'));

    // Get the AWS Credentials.
    $config = \Drupal::config('cec.settings');

    // Check if the credentials are configured.
    if (!$config->get('cec_region' && !$config->get('cec_key') && !$config->get('cec_secret'))) {
      drupal_set_message($this->t('You must configure the Global Settings correctly before execute an invalidation.'), 'error');
    }

    else {
      // Get the Paths.
      $paths = [];
      foreach ($url_value as $value) {
        if ($value) {
          $paths[] = trim($value);
        }
      }

      // Invalidate URL.
      list($status, $message) = cloudfront_edge_caching_invalidate_url($paths);

      if ($status == TRUE) {
        drupal_set_message($this->t('You invalidation is in progress.'), 'status');
      }
      else {
        drupal_set_message($this->t($message), 'error');
      }
    }
  }

}
