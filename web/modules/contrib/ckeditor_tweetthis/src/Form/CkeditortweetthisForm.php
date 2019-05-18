<?php

namespace Drupal\ckeditor_tweetthis\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * @file
 * Contains \Drupal\ckeditor_tweetthis\Form\CkedtiortweetthisForm.
 */
class CkeditortweetthisForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ckeditor_tweetthis_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ckeditor_tweetthis.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ckeditor_tweetthis.settings');
    $form['site_twitter_profile'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Site Twitter Profile'),
      '#required' => TRUE,
      '#default_value' => $config->get('site_twitter_profile'),
      '#weight' => 0,
      '#description' => $this->t('Enter the desired Twitter Profile.'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (is_numeric($form['site_twitter_profile']['#value'])) {
      $form_state->setErrorByName('site_twitter_profile', 'Please enter a valid profile name.');
    }
  }

  /**
   * Submit handler for ckeditor_tweetthis_form form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ckeditor_tweetthis.settings');
    $site_twitter_profile = $form_state->getValue(['site_twitter_profile']);
    $config->set('site_twitter_profile', $site_twitter_profile);
    $config->save();
    parent::submitForm($form, $form_state);
    drupal_flush_all_caches();
  }

}
