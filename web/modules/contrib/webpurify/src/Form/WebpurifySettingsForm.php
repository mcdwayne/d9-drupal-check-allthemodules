<?php
/**
 * @file
 * Contains \Drupal\webpurify\Form\WebpurifySettingsForm.
 */

namespace Drupal\webpurify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Controller location for Live Weather Settings Form.
 */
class WebpurifySettingsForm extends ConfigFormBase {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormId() {
    return 'webpurify_admin';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['webpurify.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	  $config = $this->config('webpurify.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General Configuration'),
      '#open' => true,
    ];

    $form['general']['webpurify_secret_key'] = [
      '#type' => 'textfield',
      '#title' => t('Webpurify secret key'),
      '#default_value' => $config->get('webpurify_secret_key'),
      '#description' => t('Enter the developer key provided to you by !link', ['!link' => '<a href="http://www.webpurify.com" target="_blank">WebPurify</a>']),
      '#required' => TRUE
    ];

    $form['general']['webpurify_replacement_symbol'] = [
      '#type' => 'textfield',
      '#title' => t('Webpurify replacement symbol'),
      '#default_value' => $config->get('webpurify_replacement_symbol'),
      '#description' => t('The replacement symbol will replace each character in a profane word.'),
      '#required' => TRUE
    ];

    $form['general']['webpurify_create_failure_mode'] = [
      '#type' => 'select',
      '#title' => t('Create Failure Mode'),
      '#description' => t('Select how you want WebPurify to handle creation when the API fails to respond.'),
      '#options' => webpurify_create_failure_modes_list(),
    ];

    $form['general']['webpurify_create_failure_text'] = [
      '#type' => 'textfield',
      '#title' => t('Create Failure Message'),
      '#description' => t('This is the text that will be displayed if entity creation will be blocked during an API failure.'),
      '#states' => [
        'visible' => [
          'select[name="webpurify_create_failure_mode"]' => [
            'value' => WEBPURIFY_CREATE_FAILURE_MODE_BLOCK
          ],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
     $form_value = $form_state->getValues();

    $this->config('webpurify.settings')
	  ->set('webpurify_secret_key', $form_value['webpurify_secret_key'])
	  ->set('webpurify_replacement_symbol', $form_value['webpurify_replacement_symbol'])
	  ->save();

    parent::submitForm($form, $form_state);
  }
}
