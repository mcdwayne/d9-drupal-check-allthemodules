<?php

namespace Drupal\shorten_urls_in_comment\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * @file
 * Contains \Drupal\shorten_urls_in_comment\Form\ShortenForm.
 */

/**
 * Implements an example form.
 */
class ShortenForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shorten_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'shorten.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('shorten.settings');
    $form['google_api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Press enter your Google API KEY'),
      '#default_value' => $config->get('google_api_key'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('shorten.settings')
      ->set('google_api_key', $form_state->getValue('google_api_key'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
