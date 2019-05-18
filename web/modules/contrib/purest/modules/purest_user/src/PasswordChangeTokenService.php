<?php

namespace Drupal\purest_user;

use Drupal\user\UserInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;

/**
 * Class PasswordChangeTokenService.
 */
class PasswordChangeTokenService implements PasswordChangeTokenServiceInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new OneTimeLoginLinkService object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager) {
    $this->configFactory = $config_factory;
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function sendPasswordChangeTokenEmail(UserInterface $account, $langcode = NULL) {
    $langcode = $langcode ? $langcode : $account->getPreferredLangcode();

    // Get the custom site notification email to use as the from email address
    // if it has been set.
    $site_config = $this->configFactory->get('system.site');
    $site_mail = $site_config->get('mail_notification');

    // If the custom site notification email has not been set, we use the site
    // default.
    if (empty($site_mail)) {
      $site_mail = $site_config->get('mail');
    }

    if (empty($site_mail)) {
      $site_mail = ini_get('sendmail_from');
    }

    $params = [
      'account' => $account,
    ];

    $mail = $this->mailManager->mail(
      'purest_user',
      'change_password_email_copy',
      $account->getEmail(),
      $langcode,
      $params,
      $site_mail
    );

    return $mail;
  }

  /**
   * {@inheritdoc}
   */
  public function changePasswordTokenUrl(UserInterface $account, $base_url = NULL) {
    $timestamp = REQUEST_TIME;
    $hash = user_pass_rehash($account, $timestamp);
    $langcode = $account->getPreferredLangcode();

    $url = Url::fromUri(
      $base_url,
      [
        'query' => [
          'id' => $account->id(),
          'timestamp' => $timestamp,
          'token' => user_pass_rehash($account, $timestamp),
        ],
        'absolute' => TRUE,
        'language' => $this->languageManager->getLanguage($langcode),
      ]
    );

    return $url->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function token(array &$replacements, array $data, array $options) {
    if (isset($data['user'])) {
      $token_service = \Drupal::token();
      $activation_url = $token_service->replace('[purest:change_url]');
      $replacements['[purest:password_change_url]'] = $this
        ->changePasswordTokenUrl($data['user'], $activation_url);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function changePassword(UserInterface $account, $token, $timestamp, $password) {
    // Time out, in seconds, until activation token expires. 24 hours = 86400
    // seconds.
    $timeout = 86400;

    $current_timestamp = REQUEST_TIME;
    $expire_timestamp = $timestamp + $timeout;

    // If the account is already active, do not continue.
    if ($account->get('status')->value != 1) {
      return FALSE;
    }

    // If the current timestamp is smaller than the expiry timestamp, and the
    // account is set, and the provided token is equal to the hash of the
    // account + provided timestamp then we can activate the account.
    if ($current_timestamp < $expire_timestamp && $account && $token === user_pass_rehash($account, $timestamp)) {
      $account->setPassword($password);
      $account->save();
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function sendPasswordChangedConfirmationEmail(UserInterface $account, $langcode = NULL) {
    $langcode = $langcode ? $langcode : $account->getPreferredLangcode();

    // Get the custom site notification email to use as the from email address
    // if it has been set.
    $site_config = $this->configFactory->get('system.site');
    $site_mail = $site_config->get('mail_notification');

    // If the custom site notification email has not been set, we use the site
    // default.
    if (empty($site_mail)) {
      $site_mail = $site_config->get('mail');
    }

    if (empty($site_mail)) {
      $site_mail = ini_get('sendmail_from');
    }

    $params = [
      'account' => $account,
    ];

    $mail = $this->mailManager->mail('purest_user', 'password_changed_email_copy',
                          $account->getEmail(), $langcode, $params, $site_mail);

    return $mail;
  }

}
