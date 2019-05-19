<?php

/**
 * @file
 * Contains \Drupal\instagram_block\Form\InstagramBlockForm.
 */

namespace Drupal\sjisocialconnect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;


/**
 * Configure instagram_block settings for this site.
 */
class InstagramBlockForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'sjisocialconnect_instagram';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sjisocialconnect.instagram'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get module configuration.
    $config = $this->config('sjisocialconnect.instagram');

    $form['authorise'] = array(
      '#markup' => t('Instagram Block requires connecting to a specific Instagram account. You need to be able to log into that account when asked to. The Authenticate with Instagram page helps with the setup.', array('%link' => 'https://www.drupal.org/node/2746185')),
    );

    $form['user_id'] = array(
      '#type' => 'textfield',
      '#title' => t('User Id'),
      '#description' => t('Your unique Instagram user id. Eg. 460786510'),
      '#default_value' => $config->get('user_id'),
    );

    $form['access_token'] = array(
      '#type' => 'textfield',
      '#title' => t('Access Token'),
      '#description' => t('Your Instagram access token. Eg. 460786509.ab103e5.a54b6834494643588d4217ee986384a8'),
      '#default_value' => $config->get('access_token'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user_id = $form_state->getValue('user_id');
    $access_token = $form_state->getValue('access_token');

    // Get module configuration.
    $this->config('sjisocialconnect.instagram')
      ->set('user_id', $user_id)
      ->set('access_token', $access_token)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
