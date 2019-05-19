<?php

namespace Drupal\social_auth_itsme;

use Drupal\Core\Language\LanguageManager;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\social_auth_itsme\Settings\ItsmeAuthSettings;
use Nascom\ItsmeApiClient\Request\Transaction\CreateTransactionRequest;
use Nascom\ItsmeApiClient\Request\Transaction\Service;
use Nascom\ItsmeApiClient\Request\Status\RetrieveStatusRequest;
use Nascom\ItsmeApiClient\Response\Status\Status;
use Nascom\ItsmeApiClient\Response\Status\Locale;
use Drupal\Core\Extension\ModuleHandler;

/**
 * Contains all the logic for itsme login integration.
 */
class ItsmeAuthManager {

  /**
   * The settings.
   *
   * @var \Drupal\social_auth_itsme\Settings\ItsmeAuthSettingsInterface
   */
  protected $settings;

  /**
   * The service client.
   *
   * @var \Nascom\ItsmeApiClient\Http\ApiClient\ApiClient
   */
  protected $client;

  /**
   * The user returned by the provider.
   *
   * @var Nascom\ItsmeApiClient\Response\Status\Status
   */
  protected $user;

  /**
   * The data handler.
   *
   * @var \Drupal\social_auth\SocialAuthDataHandler
   */
  protected $dataHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  private $languageManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  private $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function setClient($client) {
    $this->client = $client;
    return $this;
  }

  /**
   * Gets the settings.
   *
   * @param \Drupal\social_auth_itsme\Settings\ItsmeAuthSettings $settings
   *   Settings.
   *
   * @return $this
   */
  public function setSettings(ItsmeAuthSettings $settings) {
    $this->settings = $settings;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getClient() {
    return $this->client;
  }

  /**
   * ItsmeAuthManager constructor.
   *
   * @param \Drupal\social_auth\SocialAuthDataHandler $dataHandler
   *   Data handler.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   Language manager.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   Module handler.
   */
  public function __construct(SocialAuthDataHandler $dataHandler, LanguageManager $languageManager, ModuleHandler $moduleHandler) {
    $this->dataHandler = $dataHandler;
    $this->languageManager = $languageManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserInfo() {
    if (!$this->user) {
      try {
        $retrieveStatusRequest = new RetrieveStatusRequest($this->getAccessToken());
        /** @var Nascom\ItsmeApiClient\Response\Status\Status $status */
        $this->user = $this->client->handle($retrieveStatusRequest);
      }
      catch (\Exception $e) {
        return FALSE;
      }

      // Failed to authenticate.
      if ($this->user->getStatus() !== Status::SUCCESS) {
        return FALSE;
      }
    }
    return $this->user;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizationUrl() {
    // Create a transaction with the itsme API.
    $createTransactionRequest = new CreateTransactionRequest(
      $this->settings->getToken(),
      Service::LOGIN,
      $GLOBALS['base_url'] . '/user/login/itsme/callback'
    );

    // Add scopes it provided.
    if ($scopes = $this->settings->getScopes()) {
      $createTransactionRequest->setScopes($scopes);
    }

    // Load language but.
    $language_id = $this->languageManager->getCurrentLanguage()->getId();
    $this->moduleHandler->alter('social_auth_itsme_locale', $language_id);

    // Add locale to request.
    if (in_array($language_id, [Locale::EN, Locale::NL, Locale::FR, Locale::DE])) {
      $createTransactionRequest->setLocale();
    }

    try {
      /** @var \Nascom\ItsmeApiClient\Response\Transaction\Transaction $transaction */
      $transaction = $this->client->handle($createTransactionRequest);
      $this->dataHandler->set('token', $transaction->getToken());

      return $transaction->getAuthenticationUrl();
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Get the access token.
   *
   * @return mixed
   *   Token as a string.
   */
  public function getAccessToken() {
    return $this->dataHandler->get('token');
  }

}
