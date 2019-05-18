<?php

namespace Drupal\janrain_connect_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\janrain_connect\Service\JanrainConnectConnector;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Controller routines for janrain_connect_ui module routes.
 */
class EmailVerificationController extends ControllerBase {

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
   * Constructs a EmailVerificationController object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request.
   * @param \Drupal\janrain_connect\Service\JanrainConnectConnector $janrain_connector
   *   The janrain connect connector service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   */
  public function __construct(
    Request $request,
    JanrainConnectConnector $janrain_connector,
    ConfigFactory $config_factory,
    PrivateTempStoreFactory $temp_store_factory
  ) {
    $this->request = $request;
    $this->janrainConnector = $janrain_connector;
    $this->config = $config_factory->get('janrain_connect.settings');
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
      $container->get('tempstore.private')
    );
  }

  /**
   * Email Verification page to receive and validate email token.
   */
  public function emailVerificationPage() {
    $verification_code = $this->request->query->get('verification_code');

    if (empty($verification_code)) {
      return $this->getEmailVerificationPageResponseFail();
    }

    // Call janrain service to validate the verification code.
    $result = $this->janrainConnector->useVerificationCode($verification_code);

    if (!empty($result['has_errors'])) {
      return $this->getEmailVerificationPageResponseFail();
    }

    return $this->getEmailVerificationPageResponseSuccess($result);
  }

  /**
   * Returns a email verification page response fail.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
   *   A redirect response if configured in admin area or markup.
   */
  private function getEmailVerificationPageResponseFail() {
    $destination = $this->getEmailVerificationPageResponseDestination(
      $this->config->get('config_auth_email_verification_redirect_fail')
    );

    if (empty($destination)) {
      return $this->getEmailVerificationPageResponseMarkup(
        $this->t('The email verification code is invalid.')
      );
    }

    return $this->redirect($destination->getRouteName());
  }

  /**
   * Returns a email verification page response success.
   *
   * @param array $result
   *   The Janrain response success.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
   *   A redirect response if configured in admin area or markup.
   */
  private function getEmailVerificationPageResponseSuccess(array $result) {
    $destination = $this->getEmailVerificationPageResponseDestination(
      $this->config->get('config_auth_email_verification_redirect_success')
    );

    if (empty($destination)) {
      return $this->getEmailVerificationPageResponseMarkup(
        $this->t('The email was validated.')
      );
    }

    // Save janrain result data in PrivateTempStore.
    $store = $this
      ->tempStoreFactory
      ->get('janrain_connect_ui_email_verification_redirect_success');
    try {
      $store->set('result', $result);
    }
    catch (TempStoreException $e) {
    }

    return $this->redirect($destination->getRouteName());
  }

  /**
   * Get email verification page response destination.
   *
   * @param string $path
   *   The path to create Url object.
   *
   * @return \Drupal\Core\Url|null
   *   Url object or null.
   */
  private function getEmailVerificationPageResponseDestination($path) {
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

  /**
   * Get email verification page response markup.
   *
   * @param string $markup
   *   The markup.
   *
   * @return array
   *   An response markup.
   */
  private function getEmailVerificationPageResponseMarkup($markup) {
    return [
      '#type' => 'markup',
      '#markup' => $markup,
      '#cache' => ['max-age' => 0],
    ];
  }

}
