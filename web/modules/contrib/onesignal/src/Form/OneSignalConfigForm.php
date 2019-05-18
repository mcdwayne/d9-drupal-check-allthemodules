<?php

namespace Drupal\onesignal\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\onesignal\Config\ConfigManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class OneSignalConfigForm.
 */
class OneSignalConfigForm extends ConfigFormBase {
  
  /**
   * The config manager service.
   *
   * @var \Drupal\onesignal\Config\ConfigManagerInterface
   */
  private $configManager;
  
  /**
   * OneSignalConfigForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \Drupal\onesignal\Config\ConfigManagerInterface $configManager
   *   The config manager service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ConfigManagerInterface $configManager) {
    parent::__construct($configFactory);
    
    $this->configManager = $configManager;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('onesignal.config_manager')
    );
  }
  
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'onesignal.config',
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'one_signal_config_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['onesignal_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OneSignal App ID'),
      '#description' => $this->t('Find it at https://OneSignal.com under your app Settings &gt; Keys &amp; IDs.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $this->configManager->getAppId(),
    ];
    // Disable the field if its value has been set in the settings.php file.
    // This is useful when there are different configurations for development,
    // testing and live environments.
    if (!empty($this->configManager->getAppId()) && ($this->configManager->getOriginalAppId() != $this->configManager->getAppId())) {
      $form['onesignal_app_id']['#disabled'] = TRUE;
      $form['onesignal_app_id']['#description'] = $this->t('This field has been disabled because its value is being overridden in the settings.php file.');
    }
    $form['onesignal_safari_web_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OneSignal Safari App ID'),
      '#description' => $this->t('Find it at https://OneSignal.com under your app Settings &gt; Platforms &gt; Apple Safari.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $this->configManager->getSafariWebId(),
    ];
    // Disable the field if its value has been set in the settings.php file
    // This is useful when there are different configurations for development,
    // testing and live environments.
    if (!empty($this->configManager->getSafariWebId()) && ($this->configManager->getOriginalSafariWebId() != $this->configManager->getSafariWebId())) {
      $form['onesignal_safari_web_id']['#disabled'] = TRUE;
      $form['onesignal_safari_web_id']['#description'] = $this->t('This field has been disabled because its value is being overridden in the settings.php file.');
    }
    $form['onesignal_rest_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OneSignal REST API key'),
      '#description' => $this->t('NOT IN USE. In the future this field may be enabled to implement the REST API.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $this->configManager->getRestApiKey(),
      '#disabled' => TRUE,
    ];
    $form['onesignal_auto_register'] = [
      '#type' => 'select',
      '#title' => $this->t('Auto register'),
      '#description' => $this->t('Set to true to automatically prompt visitors to accept notifications.'),
      '#options' => [
        0 => 'Unset',
        'true' => $this->t('True'),
        'false' => $this->t('False'),
      ],
      '#default_value' => $this->configManager->getAutoRegister(),
    ];
    $form['onesignal_notify_button'] = [
      '#type' => 'select',
      '#title' => $this->t('Notify button visibility'),
      '#description' => $this->t('True will make the Bell visible if it has been configured at OneSignal.'),
      '#options' => [
        0 => 'Unset',
        'true' => $this->t('True'),
        'false' => $this->t('False'),
      ],
      '#default_value' => $this->configManager->getNotifyButton(),
    ];
    $form['onesignal_localhost_secure'] = [
      '#type' => 'select',
      '#title' => $this->t('Localhost secure origin'),
      '#description' => $this->t('Development setting. True allows a Locahost behave as if it had SSL.'),
      '#options' => [
        0 => 'Unset',
        'true' => $this->t('True'),
        'false' => $this->t('False'),
      ],
      '#default_value' => $this->configManager->getLocalhostSecure(),
    ];
    $form['onesignal_prompt'] = [
      '#type' => 'details',
      '#title' => $this->t('Prompt settings'),
      '#description' => $this->t('Controls the box that prompts the user to receive notifications. Leave blank to use what comes from OneSignal.'),
      '#open' => FALSE,
    ];
    // If empty get the default message from install/onesignal.settings.yml
    $form['onesignal_action_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Action Message'),
      '#description' => $this->t('Text of the invitation to signup for both the HTTP prompt and the browser popup.'),
      '#maxlength' => 90,
      '#size' => 64,
      '#default_value' => $this->configManager->getActionMessage(),
      '#group' => 'onesignal_prompt',
    ];
    // If empty get the default message from install/onesignal.settings.yml
    $form['onesignal_accept_button'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Accept Button'),
      '#description' => $this->t('Text of the Accept button.'),
      '#maxlength' => 15,
      '#size' => 15,
      '#default_value' => $this->configManager->getAcceptButtonText(),
      '#group' => 'onesignal_prompt',
    ];
    // If empty get the default message from install/onesignal.settings.yml
    $form['onesignal_cancel_button'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cancel Button'),
      '#description' => $this->t('Text of the Cancel button.'),
      '#maxlength' => 15,
      '#size' => 15,
      '#default_value' => $this->configManager->getCancelButtonText() ,
      '#group' => 'onesignal_prompt',
    ];
    $form['onesignal_welcome'] = [
      '#type' => 'details',
      '#title' => $this->t('Welcome settings'),
      '#description' => $this->t('Controls the first message sent confirming the sign up. Leave blank to use what comes from OneSignal.'),
      '#open' => FALSE,
    ];
    // If the title is not set we use the site name.
    $form['onesignal_welcome_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Welcome Notification Title'),
      '#description' => $this->t('Title of the first notification the user receives confirming the enrollment to receive notifications'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $this->configManager->getWelcomeTitle(),
      '#group' => 'onesignal_welcome',
    ];
    // If empty get the default message from install/onesignal.settings.yml
    $form['onesignal_welcome_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Welcome Notification Message'),
      '#description' => $this->t('Body of the first notification the user receives confirming the enrollment to receive notifications'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $this->configManager->getWelcomeMessage(),
      '#group' => 'onesignal_welcome',
    ];
    return parent::buildForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    
    $this->config('onesignal.config')
      ->set('onesignal_app_id', $form_state->getValue('onesignal_app_id'))
      ->set('onesignal_safari_web_id', $form_state->getValue('onesignal_safari_web_id'))
      ->set('onesignal_rest_api_key', $form_state->getValue('onesignal_rest_api_key'))
      ->set('onesignal_action_message', $form_state->getValue('onesignal_action_message'))
      ->set('onesignal_accept_button', $form_state->getValue('onesignal_accept_button'))
      ->set('onesignal_cancel_button', $form_state->getValue('onesignal_cancel_button'))
      ->set('onesignal_welcome_title', $form_state->getValue('onesignal_welcome_title'))
      ->set('onesignal_welcome_message', $form_state->getValue('onesignal_welcome_message'))
      ->set('onesignal_auto_register', $form_state->getValue('onesignal_auto_register'))
      ->set('onesignal_notify_button', $form_state->getValue('onesignal_notify_button'))
      ->set('onesignal_localhost_secure', $form_state->getValue('onesignal_localhost_secure'))
      ->save();
  }
  
}
