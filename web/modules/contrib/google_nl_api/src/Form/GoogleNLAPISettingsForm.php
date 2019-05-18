<?php

namespace Drupal\google_nl_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\encryption\EncryptionService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Google\Cloud\Language\LanguageClient;

/**
 * Configure Google NL API settings for this site.
 */
class GoogleNLAPISettingsForm extends ConfigFormBase {

  /**
   * The encryption service.
   *
   * @var \Drupal\encryption\EncryptionService
   */
  protected $encryption;

  /**
   * Constructs a \Drupal\google_nl_api\Form\GoogleNLAPISettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\encryption\EncryptionService $encryption
   *   The encryption service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EncryptionService $encryption) {
    parent::__construct($config_factory);
    $this->encryption = $encryption;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('encryption')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_nl_api_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['google_nl_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_nl_api.settings');

    $form['key_file_contents'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Google key file contents'),
      '#default_value' => $config->get('key_file_contents') ? $this->encryption->decrypt($config->get('key_file_contents'), TRUE) : '',
      '#required' => TRUE,
      '#description' => $this->t('The contents of the Google NL API key file (.json). See <a href="@url">Google\'s documentation</a> to create a set up authentication.', [
        '@url' => 'https://cloud.google.com/natural-language/docs/reference/libraries#setting_up_authentication',
      ]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Detects the sentiment of the text.
    try {
      $keyFileData = json_decode($form_state->getValue('key_file_contents'), TRUE);

      // Test project id.
      $language = new LanguageClient([
        'keyFile' => $keyFileData,
      ]);

      $annotation = $language->analyzeSentiment('Hello, world');
      $annotation->sentiment();
      $this->messenger()->addStatus($this->t('Google NL API test succeeded.'));
    }
    catch (\Exception $e) {
      $form_state->setErrorByName('project_id', $this->t('Google NL API test failed. Please confirm project ID settings.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This is built with an array for future settings to be added easily.
    $encrypted_values = [
      'key_file_contents' => $this->encryption->encrypt($form_state->getValue('key_file_contents'), TRUE),
    ];
    $encryption_error = FALSE;

    foreach ($encrypted_values as $encrypted_value) {
      if (!$encrypted_value) {
        $encryption_error = TRUE;
        break;
      }
    }

    if ($encryption_error) {
      $this->messenger()->addError($this->t('Failed to encrypt values in the form. Please ensure that the Encryption module is enabled and that an encryption key is set.'));
    }
    else {
      $config = $this->config('google_nl_api.settings');

      foreach ($encrypted_values as $key => $encrypted_value) {
        $config->set($key, $encrypted_value);
      }
      $config->save();

      parent::submitForm($form, $form_state);
    }
  }

}
