<?php  
/**  
 * @file  
 * Contains Drupal\zoom_conference\Form\ZoomAdminForm.  
 */

namespace Drupal\zoom_conference\Form;  
use Drupal\Core\Form\ConfigFormBase;  
use Drupal\Core\Form\FormStateInterface;  

class ZoomAdminForm extends ConfigFormBase {

 /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'zoom_conference.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'zoom_conference_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('zoom_conference.settings');  

    // API Key.
    $form['zoom_conference_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zoom API Key'),
      '#description' => 'Get your Zoom account here: http://bit.ly/2Er4MjK',
      '#default_value' => $config->get('zoom_conference_key'),
      '#required' => TRUE,
    ];

    // API Secret.
    $form['zoom_conference_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zoom API Secret'),
      '#description' => 'https://zoom.us/developer/api/credential',
      '#default_value' => $config->get('zoom_conference_secret'),
      '#required' => TRUE,
    ];

    // API URL.
    $form['zoom_conference_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zoom API URL'),
      '#default_value' => $config->get('zoom_conference_url'), // Default = 'api.zoom.us/v2/'
      '#field_prefix' => 'https://',
      '#field_suffix' => '/',
      '#required' => TRUE,
    ];

    // Enable webhooks.
    $form['zoom_conference_webhooks_enabled'] = [
      '#title' => $this->t('Enable Webhooks'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('zoom_conference_webhooks_enabled'), // Default = 0
      '#description' => 'https://zoom.github.io/api/?shell#webhooks',
    ];

    // Webhooks username.
    $form['zoom_conference_webhooks_username'] = [
      '#title' => $this->t('HTTP Basic Auth Username'),
      '#type' => 'textfield',
      '#default_value' => $config->get('zoom_conference_webhooks_username'),
      '#states' => [
        'visible' => [
          'input[name="zoom_conference_webhooks_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Webhooks Password.
    $form['zoom_conference_webhooks_password'] = [
      '#title' => $this->t('HTTP Basic Auth Password'),
      '#type' => 'textfield',
      '#default_value' => $config->get('zoom_conference_webhooks_password'),
      '#states' => [
        'visible' => [
          'input[name="zoom_conference_webhooks_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Enable Debug Mode.
    $form['zoom_conference_debug'] = [
      '#title' => $this->t('Enable Debug Mode'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('zoom_conference_debug'), // Default = 0
      '#description' => $this->t('Turns on logging.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('zoom_conference.settings')
      ->set('zoom_conference_key', $form_state->getValue('zoom_conference_key'))
      ->set('zoom_conference_secret', $form_state->getValue('zoom_conference_secret'))
      ->set('zoom_conference_url', $form_state->getValue('zoom_conference_url'))
      ->set('zoom_conference_webhooks_enabled', $form_state->getValue('zoom_conference_webhooks_enabled'))
      ->set('zoom_conference_webhooks_username', $form_state->getValue('zoom_conference_webhooks_username'))
      ->set('zoom_conference_webhooks_password', $form_state->getValue('zoom_conference_webhooks_password'))
      ->set('zoom_conference_debug', $form_state->getValue('zoom_conference_debug'))
      ->save();
  }

}
