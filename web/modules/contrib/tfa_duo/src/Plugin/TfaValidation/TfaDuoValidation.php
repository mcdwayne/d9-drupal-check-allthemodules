<?php

namespace Drupal\tfa_duo\Plugin\TfaValidation;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\HTML;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\tfa\Plugin\TfaBasePlugin;
use Drupal\tfa\Plugin\TfaValidationInterface;
use Drupal\user\UserDataInterface;
use Duo\Web;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Duo validation class for performing Duo validation.
 *
 * @TfaValidation(
 *   id = "tfa_duo",
 *   label = @Translation("Tfa Duo validation"),
 *   description = @Translation("Tfa Duo Validation Plugin"),
 *   isFallback = FALSE
 * )
 */
class TfaDuoValidation extends TfaBasePlugin implements TfaValidationInterface {
  use MessengerTrait;

  /**
   * Object containing the external validation library.
   *
   * @var \Duo\Web
   */
  protected $duo;

  /**
   * This plugin's settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * The KeyRepository.
   *
   * @var \Drupal\key\KeyRepository
   */
  protected $keyRepository;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserDataInterface $user_data, EncryptionProfileManagerInterface $encryption_profile_manager, EncryptServiceInterface $encrypt_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $user_data, $encryption_profile_manager, $encrypt_service);

    $this->duo = new Web();
    $plugin_settings = \Drupal::config('tfa.settings')->get('validation_plugin_settings');
    $this->settings = !empty($plugin_settings['tfa_duo']) ? $plugin_settings['tfa_duo'] : [];
    $this->keyRepository = \Drupal::service('key.repository');
  }

  /**
   * {@inheritdoc}
   */
  public function ready() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array $form, FormStateInterface $form_state) {
    $key = $this->keyRepository->getKey($this->settings['duo_key'])->getKeyValues();

    // Iframe posts to this same page.
    if ($username = $this->duoVerifiedLogin($key['duo_integration'], $key['duo_secret'], $key['duo_application'])) {
      $this->doDrupalLogin($username);
    }

    $sign_request = $this->duo->signRequest($key['duo_integration'], $key['duo_secret'], $key['duo_application'], $form_state->getBuildInfo()['args'][0]->getUsername());

    if ($this->responseErrors($sign_request)) {
      return $form;
    }

    $form['#title'] = t('Duo 2nd-Factor Login');
    $form['duo'] = [
      '#type' => 'inline_template',
      '#template' => '<iframe id="duo_iframe" data-host="{{ host }}" data-sig-request="{{ sign_request }}"></iframe>',
      '#context' => [
        'host' => $key['duo_apihostname'],
        'sign_request' => $sign_request,
      ],
    ];

    $form['#attached'] = [
      'library' => [
        'tfa_duo/duo-sign',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm($config, $state) {
    $form = [];
    $form['duo_key'] = [
      '#type' => 'key_select',
      '#key_filters' => ['type' => 'duo'],
      '#title' => t('Duo key'),
      '#default_value' => !empty($this->settings['duo_key']) ? $this->settings['duo_key'] : '',
      '#description' => t('The Duo key with all the credentials from the Duo administrative interface'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbacks() {
    return ($this->pluginDefinition['fallbacks']) ?: '';
  }

  /**
   * {@inheritdoc}
   */
  public function isFallback() {
    return ($this->pluginDefinition['isFallback']) ?: FALSE;
  }

  /**
   * Helper method to handle the Duo iframe POST.
   *
   * @return mixed
   *   User name if logged in or false.
   */
  public function duoVerifiedLogin($ikey, $skey, $akey) {
    $name = FALSE;
    if (!empty($_POST) && isset($_POST['sig_response'])) {
      $sig_resp = trim($_POST['sig_response']);
      $action = trim($_POST['reset']);
      if (!$sig_resp) {
        $this->messenger()->addError("The required information wasn't received. Please try again.");
        return FALSE;
      }
      $name = $this->duo->verifyResponse($ikey, $skey, $akey, $sig_resp);
      $this->responseErrors($name);
      if (!$name || !user_load_by_name($name)) {
        $this->messenger()->addError("Secondary authentication credentials were invalid, or the user cannot be loaded.");
        return FALSE;
      }
    }
    return $name;
  }

  /**
   * Helper function to perform a Drupal login and redirect.
   *
   * @param string $name
   *   The user name to login with.
   */
  public function doDrupalLogin($name) {
    if (!$name) {
      return;
    }
    $user = user_load_by_name($name);
    // @TODO Do we need error checking here?
    \Drupal::logger('user')->info('User %name has authenticated with Duo.', ['%name' => $user->getDisplayName()]);
    user_login_finalize($user);
    /** @var \Drupal\Core\Routing\UrlGenerator $generator */
    $generator = \Drupal::service('url_generator');
    $redirect = new RedirectResponse($generator->generateFromRoute('user.page'));

    // Handle user reset requests (UNTESTED)
    if (isset($_POST['reset']) && trim($_POST['reset']) == TRUE) {
      $token = Crypt::randomBytesBase64(55);
      $_SESSION['pass_reset_' . $user->id()] = $token;
      $timestamp = REQUEST_TIME;
      $redirect = new RedirectResponse($generator->generateFromRoute('user.reset'), [
        'uid' => $user->id(),
        'timestamp' => $timestamp,
        'hash' => user_pass_rehash($user, $timestamp),
      ]);
    }

    $redirect->send();
  }

  /**
   * Helper function to deal with errors from Duo API calls.
   *
   * @param string $response
   *   The response string from Duo.
   * @param bool $verbose
   *   Whether or not to log the error message.
   *
   * @return bool
   *   Whether or not there were errors.
   */
  public function responseErrors($response, $verbose = TRUE) {
    list($response_code, $message) = explode('|', $response);
    $has_errors = ($response_code == 'ERR');
    if ($has_errors && $verbose) {
      $this->messenger()->addError(HTML::escape($message));
    }
    return $has_errors;
  }

}
