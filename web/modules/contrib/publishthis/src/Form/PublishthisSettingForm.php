<?php

namespace Drupal\publishthis\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Implements an example form.
 */
class PublishthisSettingForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'publishthis_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'publishthis.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;

    $config = $this->config('publishthis.settings');

    // Adding css.
    $form['#attached']['library'][] = 'publishthis/publishthis-settings-css';

    $form['publishthis_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Publishthis Settings'),
    ];

    $form['publishthis_fieldset']['pt_curated_publish'] = [
      '#type' => 'radios',
      '#title' => $this->t('PublishThis Curated Publish Options'),
      '#attributes'  => [
        'class' => [
          'horizontal-list',
        ],
      ],
      '#options' => [
        'publishthis_import_from_manager' => $this->t('PublishThis pushes to this CMS'),
      ],
      '#default_value' => !empty($config->get('pt_curated_publish')) ? $config->get('pt_curated_publish') : 'publishthis_import_from_manager',
    ];

    if (!empty($config->get('pt_endpoint'))) {
      $pt_endpoint = $config->get('pt_endpoint');
    }
    else {
      $pt_endpoint = publishthis_get_random_word();
    }

    $form['publishthis_fieldset']['pt_endpoint'] = [
      '#type' => 'textfield',
      '#default_value' => $pt_endpoint,
      '#required' => TRUE,
      '#prefix' => '<div class="js-form-item form-item js-form-type-textfield form-type-textfield">
                      <label for="edit-pt-endpoint"><b>CMS URL (Endpoint)</b></label>
                      <div id="edit-cms-url">
                        <div class="form-item form-type-checkbox">' . $base_url . '/publishthis?q=' . $pt_endpoint . '</div>
                      </div>
                    </div>
                    <div id="pt_endpoint_input">',
      '#suffix' => '</div>',
    ];

    $form['publishthis_fieldset']['api_token'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API Token'),
      '#weight' => 2,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['publishthis_fieldset']['api_token']['pt_api_token_verfiy_msg'] = [
      '#markup' => '<div class="token_verfiy_msg"></div>',
      '#allowed_tags' => ['div'],
    ];

    $form['publishthis_fieldset']['api_token']['pt_api_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Token'),
      '#default_value' => !empty($config->get('pt_api_token')) ? $config->get('pt_api_token') : '',
      '#required' => TRUE,
      '#prefix' => '<div class="publishthis-token-setup-block"><p>To authenticate your API token, paste your API token in the field below.</p>',
      '#suffix' => '</div>',
    ];

    $form['publishthis_fieldset']['api_token']['pt_api_token_verfiy'] = [
      '#type'  => 'button',
      '#value' => $this->t('Verify'),
      '#ajax'  => [
        'callback' => '::apiTokenValidateCallback',
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function apiTokenValidateCallback(array &$form, FormStateInterface $form_state) {
    $publishthisApi = new \Drupal\publishthis\Classes\Publishthis_API();

    $token = $publishthisApi->validate_token($form_state->getValue('pt_api_token'));

    if ($token['valid'] == 1) {
      $error_class = "messages--status";
    }
    else {
      $error_class = "messages--error";
    }

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('.token_verfiy_msg', '<div class="messages ' . $error_class . '">' . $token['message'] . '</div>'));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (strlen(trim($form_state->getValue('pt_api_token'))) < 1) {
      $form_state->setErrorByName('pt_api_token', $this->t('Api Token cannot be blank.'));
    }
    else {
      $publishthisApi = new \Drupal\publishthis\Classes\Publishthis_API();

      $token = $publishthisApi->validate_token($form_state->getValue('pt_api_token'));
      if ($token['valid'] != 1) {
        $form_state->setErrorByName('pt_api_token', $this->t('Please enter a valid Api Token.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save config.
    $this->config('publishthis.settings')
      ->set('pt_curated_publish', $form_state->getValue('pt_curated_publish'))
      ->set('pt_endpoint', $form_state->getValue('pt_endpoint'))
      ->set('pt_api_token', $form_state->getValue('pt_api_token'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
