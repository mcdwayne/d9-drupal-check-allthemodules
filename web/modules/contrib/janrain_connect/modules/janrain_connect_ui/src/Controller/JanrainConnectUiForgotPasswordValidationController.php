<?php

namespace Drupal\janrain_connect_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\Core\Url;
use Drupal\janrain_connect_settings\Constants\JanrainConnectSettingsConstants;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\janrain_connect\Service\JanrainConnectConnector;
use Drupal\janrain_connect_settings\Service\JanrainConnectSettingsService;
use Drupal\janrain_connect\Constants\JanrainConnectWebServiceConstants;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Controller routines for janrain_connect_ui module routes.
 */
class JanrainConnectUiForgotPasswordValidationController extends ControllerBase {

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * JanrainConnectConnector.
   *
   * @var \Drupal\janrain_connect\Service\JanrainConnectConnector
   */
  protected $janrainConnector;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The janrain connect settings service.
   *
   * @var \Drupal\janrain_connect_settings\Service\JanrainConnectSettingsService
   */
  protected $janrainConnectSettingsService;

  /**
   * Constructs a ForgotPasswordValidationController object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request.
   * @param \Drupal\janrain_connect\Service\JanrainConnectConnector $janrain_connector
   *   The janrain connect connector service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\janrain_connect_settings\Service\JanrainConnectSettingsService $janrain_connect_settings_service
   *   The janrain connect settings service.
   */
  public function __construct(
    Request $request,
    JanrainConnectConnector $janrain_connector,
    ConfigFactory $config_factory,
    PrivateTempStoreFactory $temp_store_factory,
    JanrainConnectSettingsService $janrain_connect_settings_service
  ) {
    $this->request = $request;
    $this->janrainConnector = $janrain_connector;
    $this->config = $config_factory->get('janrain_connect.settings');
    $this->janrainConnectSettingsService = $janrain_connect_settings_service;
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('janrain_connect.connector'),
      $container->get('config.factory'),
      $container->get('tempstore.private'),
      $container->get('janrain_connect_settings.settings')
    );
  }

  /**
   * Forgot password URL to receive and validate email token.
   */
  public function forgotPasswordValidation() {
    $code = $this->request->query->get('code');

    if (empty($code)) {
      return $this->getForgotPasswordPageResponseFail();
    }

    $redirectUri = $this
      ->janrainConnectSettingsService
      ->getJanrainSettings(
        JanrainConnectSettingsConstants::JANRAIN_CONNECT_SETTINGS_PASSWORD_RECOVER_URL
      );

    if (empty($redirectUri)) {
      return $this->getForgotPasswordPageResponseFail();
    }

    // Call janrain service to validate the code.
    try {
      $result = $this->janrainConnector->useCode('authorization_code', $code, $redirectUri);
    }
    catch (\Exception $e) {
      return $this->getForgotPasswordPageResponseFail();
    }

    if (!empty($result['has_errors'])) {
      return $this->getForgotPasswordPageResponseFail();
    }

    return $this->getForgotPasswordPageResponseSuccess($result);
  }

  /**
   * Returns a reset password page response fail.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
   *   A redirect response if configured in admin area or markup.
   */
  private function getForgotPasswordPageResponseFail() {
    $destination = $this->getForgotPasswordPageResponseDestination(
      $this->config->get('config_forgot_password_verification_redirect_fail')
    );

    if (empty($destination)) {
      return $this->redirect(
        'janrain_connect_ui.form',
        [
          'form_id' => JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_FORGOT_PASSWORD,
        ]
      );
    }

    return $this->redirect($destination->getRouteName());
  }

  /**
   * Returns a reset password response success.
   *
   * @param array $result
   *   The Janrain response success.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
   *   A redirect response if configured in admin area or markup.
   */
  private function getForgotPasswordPageResponseSuccess(array $result) {
    $destination = $this->getForgotPasswordPageResponseDestination(
      $this->config->get('config_forgot_password_verification_redirect_success')
    );

    // Save janrain result data in PrivateTempStore.
    $store = $this
      ->tempStoreFactory
      ->get('janrain_connect_ui_forgot_password_redirect_success');
    try {
      $store->set('result', $result);
    }
    catch (TempStoreException $e) {
    }

    if (empty($destination)) {
      return $this->redirect(
        'janrain_connect_ui.form',
        [
          'form_id' => JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_CHANGE_PASSWORD_FORGOTTEN,
        ]
      );
    }

    return $this->redirect($destination->getRouteName());
  }

  /**
   * Get reset password page response destination.
   *
   * @param string $path
   *   The path to create Url object.
   *
   * @return \Drupal\Core\Url|null
   *   Url object or null.
   */
  private function getForgotPasswordPageResponseDestination($path) {
    try {
      $destination = Url::fromUserInput($path);
    }
    catch (\InvalidArgumentException $e) {
      return NULL;
    }

    // Indicates if this Url has a Drupal route.
    if ($destination->isRouted()) {
      return $destination;
    }

    return NULL;
  }

}
