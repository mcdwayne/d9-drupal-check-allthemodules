<?php

namespace Drupal\token_login\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\user\Entity\User;

/**
 * Class TokenLoginForm.
 *
 * @package Drupal\token_login\Form
 */
class TokenLoginForm extends FormBase {

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * TokenLoginForm object constructor.
   *
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(UserStorageInterface $user_storage, LanguageManagerInterface $language_manager) {
    $this->userStorage = $user_storage;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('user'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'token_login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#description' => $this->t('Send one-time login link to this email address.'),
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = ['#type' => 'submit', '#value' => $this->t('Request login')];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $mail = trim($form_state->getValue('mail'));
    // Validate the email address for format.
    if (!\Drupal::service('email.validator')->isValid($mail)) {
      $form_state->setErrorByName('mail', $this->t('Malformed email address.'));
    }
    // Try to load by email.
    $users = $this->userStorage->loadByProperties(['mail' => $mail]);
    $account = reset($users);

    // We do not issue any warnings to avoid enumeration type vulnerabilities.
    if ($account && $account->id()) {
      $form_state->setValueForElement(['#parents' => ['account']], $account);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Set a generic message for every submission to avoid disclosing
    // account information.
    drupal_set_message($this->t('One-time login link has been sent to your email address. Please make sure
      to check the spam folder if you have not received an email, or contact the
      site administrator.'));

    $langcode = $this->languageManager->getCurrentLanguage()->getId();

    $account = $form_state->getValue('account');

    $reset_email = $form_state->getValue('mail');

    if (!empty($account) && $this->validateEmail($reset_email)) {
      $mail = $this->emailNotify($account, $langcode);
      if (!empty($mail)) {
        $this->logger('user')->notice('Login instructions mailed to %name at %email.',
          ['%name' => $account->getUsername(), '%email' => $account->getEmail()]);
      }
      $form_state->setRedirect('user.login');
    }
    elseif (!$this->validateEmail($form_state->getValue('mail'))) {
      $this->logger('user')->notice('Login instructions requested for a non-whitelisted domain: %email',
        ['%email' => $reset_email]);
    }
    elseif (empty($account)) {
      $this->logger('user')->notice('Login instructions requested for a non-existent account: %email',
        ['%email' => $reset_email]);
    }
  }

  /**
   * Checks if a given email address can be used for login.
   *
   * @param string $email
   *   Email address to be checked against the whitelist.
   *
   * @return bool
   *   Flag indicating if the email domain is part of the whitelist.
   */
  private function validateEmail($email) {
    $config = \Drupal::config('token_login.settings');
    $whitelist = explode("\n", $config->get('allowed_domains'));
    $whitelist = array_map('trim', $whitelist);
    $domain = substr(strrchr($email, "@"), 1);
    return in_array($domain, $whitelist);
  }

  /**
   * Helper function to send out the email with the login token.
   *
   * @param \Drupal\user\Entity\User $account
   *   The Drupal user object for the account to send out the email for.
   * @param string $langcode
   *   The language code to be used for the email.
   *
   * @return mixed
   *   The email result if the email was build, or NULL.
   */
  private function emailNotify(User $account, $langcode = '') {
    $params['account'] = $account;
    $langcode = $langcode ? $langcode : $account->getPreferredLangcode();
    $mail = \Drupal::service('plugin.manager.mail')->mail('token_login', 'login_link', $account->getEmail(), $langcode, $params);
    return empty($mail) ? NULL : $mail['result'];
  }

}
