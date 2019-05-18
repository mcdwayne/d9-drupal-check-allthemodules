<?php

namespace Drupal\pubkey_encrypt\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Url;
use Drupal\pubkey_encrypt\Plugin\AsymmetricKeysManager;
use Drupal\pubkey_encrypt\Plugin\LoginCredentialsManager;
use Drupal\pubkey_encrypt\PubkeyEncryptManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form builder for the Pubkey Encrypt initialization settings form.
 */
class PubkeyEncryptInitializationSettingsForm extends ConfigFormBase {

  /**
   * The plugin manager for asymmetric keys.
   *
   * @var \Drupal\pubkey_encrypt\Plugin\AsymmetricKeysManager
   */
  protected $asymmetricKeysManager;

  /**
   * The plugin manager for login credentials.
   *
   * @var \Drupal\pubkey_encrypt\Plugin\LoginCredentialsManager
   */
  protected $loginCredentialsManager;

  /**
   * Pubkey Encrypt manager service.
   *
   * @var \Drupal\pubkey_encrypt\PubkeyEncryptManager
   */
  protected $pubkeyEncryptManager;

  /**
   * Constructs a PubkeyEncryptInitializationSettingsForm object.
   *
   * @param \Drupal\pubkey_encrypt\Plugin\AsymmetricKeysManager $asymmetric_keys_manager
   *   Asymmetric Keys Manager plugin type.
   * @param \Drupal\pubkey_encrypt\Plugin\LoginCredentialsManager $login_credentials_manager
   *   Login Credentials Manager plugin type.
   * @param \Drupal\pubkey_encrypt\PubkeyEncryptManager $pubkey_encrypt_manager
   *   Pubkey Encrypt service.
   */
  public function __construct(AsymmetricKeysManager $asymmetric_keys_manager, LoginCredentialsManager $login_credentials_manager, PubkeyEncryptManager $pubkey_encrypt_manager) {
    $this->asymmetricKeysManager = $asymmetric_keys_manager;
    $this->loginCredentialsManager = $login_credentials_manager;
    $this->pubkeyEncryptManager = $pubkey_encrypt_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.pubkey_encrypt.asymmetric_keys'),
      $container->get('plugin.manager.pubkey_encrypt.login_credentials'),
      $container->get('pubkey_encrypt.pubkey_encrypt_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pubkey_encrypt_admin_initialization_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pubkey_encrypt.initialization_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['#prefix'] = '<div id="initialization-settings-form-wrapper">';
    $form['#suffix'] = '</div>';

    $config = $this->config('pubkey_encrypt.initialization_settings');

    // Options for Asymmetric Keys Generator plugin.
    $options = [];
    foreach ($this->asymmetricKeysManager->getDefinitions() as $plugin) {
      $options[$plugin['id']] = (string) $plugin['name'];
    }
    $form['asymmetric_keys_generator'] = array(
      '#type' => 'select',
      '#title' => $this->t('Asymmetric Keys Generator'),
      '#description' => $this->t('Select the plugin which Pubkey Encrypt should use for operations involving asymmetric keys.'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $config->get('asymmetric_keys_generator'),
      // Don't allow the plugin to change if the module has been initialized.
      '#disabled' => $config->get('module_initialized'),
      '#ajax' => array(
        'callback' => [$this, 'ajaxUpdateSettings'],
        'wrapper' => 'initialization-settings-form-wrapper',
      ),
    );

    // Configuration options for selected Asymmetric Keys Generator plugin.
    $form['asymmetric_keys_generator_configuration'] = array(
      '#type' => 'container',
      '#title' => $this->t('Asymmetric Keys Generator settings'),
      '#title_display' => FALSE,
      '#tree' => TRUE,
    );

    $selected_asymmetric_keys_generator = $form_state
      ->getValue('asymmetric_keys_generator');

    if ($selected_asymmetric_keys_generator != NULL) {
      $selected_asymmetric_keys_generator = $this
        ->asymmetricKeysManager
        ->createInstance($selected_asymmetric_keys_generator);
      if ($selected_asymmetric_keys_generator instanceof PluginFormInterface) {
        $plugin_form_state = $this->createPluginFormState($form_state);
        $form['asymmetric_keys_generator_configuration'] += $selected_asymmetric_keys_generator
          ->buildConfigurationForm([], $form_state);
        $form_state->setValue('asymmetric_keys_generator_configuration', $plugin_form_state->getValues());
      }
    }

    // Options for Login Credentials Provider plugin.
    $options = [];
    foreach ($this->loginCredentialsManager->getDefinitions() as $plugin) {
      $options[$plugin['id']] = (string) $plugin['name'];
    }
    $form['login_credentials_provider'] = array(
      '#type' => 'select',
      '#title' => $this->t('Login Credentials Provider'),
      '#description' => $this->t('Select the plugin which Pubkey Encrypt should use for operations involving login credentials.'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $config->get('login_credentials_provider'),
      // Don't allow the plugin to change if the module has been initialized.
      '#disabled' => $config->get('module_initialized'),
    );

    // Overwrite submit button provided by ConfigFormBase.
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Initialize module'),
      '#button_type' => 'primary',
    );

    // Remove the submit button from form if the module has already been
    // initialized and notify the user about it.
    if ($config->get('module_initialized')) {
      unset($form['actions']['submit']);
      drupal_set_message($this->t('The module has been initialized. You cannot change these settings now.'), 'warning');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Only validate when submitting the form, not on AJAX rebuild.
    if (!$form_state->isSubmitted()) {
      drupal_set_message($this->t('Submission of this form will log out all users from the website.'), 'warning');
      return;
    }

    $asymmetric_keys_generator_configuration = $form_state
      ->getValue('asymmetric_keys_generator_configuration');
    $asymmetric_keys_generator = $this
      ->asymmetricKeysManager
      ->createInstance($form_state->getValue('asymmetric_keys_generator'), $asymmetric_keys_generator_configuration);

    // Validate the Asymmetric Keys Generator plugin configuration.
    if ($asymmetric_keys_generator instanceof PluginFormInterface) {
      $plugin_form_state = $this->createPluginFormState($form_state);
      $asymmetric_keys_generator->validateConfigurationForm($form, $plugin_form_state);
      $this->moveFormStateErrors($plugin_form_state, $form_state);
      $this->moveFormStateStorage($plugin_form_state, $form_state);
    }

    // Validate that the Asymmetric Keys Generator plugin is working.
    $keys = $asymmetric_keys_generator->generateAsymmetricKeys();
    if (in_array('', $keys) || in_array('NULL', $keys)) {
      $form_state->setErrorByName('asymmetric_keys_generator', 'The Asymmetric Keys Generator plugin is not working.');
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Submit plugin configuration if available.
    $asymmetric_keys_generator_configuration = $form_state
      ->getValue('asymmetric_keys_generator_configuration');
    $asymmetric_keys_generator = $this
      ->asymmetricKeysManager
      ->createInstance($form_state->getValue('asymmetric_keys_generator'), $asymmetric_keys_generator_configuration);
    if ($asymmetric_keys_generator instanceof PluginFormInterface) {
      $plugin_form_state = $this->createPluginFormState($form_state);
      $asymmetric_keys_generator->submitConfigurationForm($form, $plugin_form_state);
    }

    // Save the configuration.
    $this->config('pubkey_encrypt.initialization_settings')
      ->set('module_initialized', 1)
      ->set('asymmetric_keys_generator', $form_state->getValue('asymmetric_keys_generator'))
      ->set('asymmetric_keys_generator_configuration', $form_state->getValue('asymmetric_keys_generator_configuration'))
      ->set('login_credentials_provider', $form_state->getValue('login_credentials_provider'))
      ->save();

    // Initialize the module.
    $this->pubkeyEncryptManager->initializeModule();

    // Redirect user to homepage since he would be logged out after module
    // initialization.
    $form_state->setRedirectUrl(Url::fromUserInput('/'));
  }

  /**
   * AJAX callback to update the dynamic settings on the form.
   *
   * @param array $form
   *   The form definition array for the encryption profile form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The element to update in the form.
   */
  public function ajaxUpdateSettings(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Creates a FormStateInterface object for a plugin.
   *
   * @param FormStateInterface $form_state
   *   The form state to copy values from.
   *
   * @return FormStateInterface
   *   A clone of the form state object with values from the plugin.
   */
  protected function createPluginFormState(FormStateInterface $form_state) {
    // Clone the form state.
    $plugin_form_state = clone $form_state;

    // Clear the values, except for this plugin type's configuration.
    $plugin_form_state->setValues($form_state->getValue('asymmetric_keys_generator_configuration', []));

    return $plugin_form_state;
  }

  /**
   * Moves form errors from one form state to another.
   *
   * @param \Drupal\Core\Form\FormStateInterface $from
   *   The form state object to move from.
   * @param \Drupal\Core\Form\FormStateInterface $to
   *   The form state object to move to.
   */
  protected function moveFormStateErrors(FormStateInterface $from, FormStateInterface $to) {
    foreach ($from->getErrors() as $name => $error) {
      $to->setErrorByName($name, $error);
    }
  }

  /**
   * Moves storage variables from one form state to another.
   *
   * @param \Drupal\Core\Form\FormStateInterface $from
   *   The form state object to move from.
   * @param \Drupal\Core\Form\FormStateInterface $to
   *   The form state object to move to.
   */
  protected function moveFormStateStorage(FormStateInterface $from, FormStateInterface $to) {
    foreach ($from->getStorage() as $index => $value) {
      $to->set($index, $value);
    }
  }

}
