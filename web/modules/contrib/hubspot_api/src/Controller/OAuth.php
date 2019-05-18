<?php

namespace Drupal\hubspot_api\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\hubspot_api\Services\OAuth as OAuthService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller Class for OAuth Functionality.
 */
class OAuth extends ControllerBase {

  /**
   * The OAuth service.
   */
  protected $oauthService;

  /**
   * The config factory to use.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|null
   */
  protected $configFactory;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs an OAuth object.
   *
   * @param \Drupal\hubspot_api\Services\OAuth $oauth_service
   *   The OAuth service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(OAuthService $oauth_service, ConfigFactoryInterface $config_factory,  MessengerInterface $messenger) {
    $this->oauthService = $oauth_service;
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('hubspot_api.oauth'),
      $container->get('config.factory'),
      $container->get('messenger')
    );
  }

  /**
   * Handles responses from Hubspot's OAuth redirect.
   *
   * Grabs the 'code' sent by Hubspot in the url. This code is then passed along
   * to save the tokens created.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function oauthRedirect() {
    if (isset($_GET['error']) && $_GET['error'] == 'access_denied') {
      // Show error message if access was denied.
      $this->messenger->addError($this->t('Access was denied to HubSpot API.'));
    }

    if (!isset($_GET['code'])) {
      // Show error message missing the response code.
      $this->messenger->addError($this->t('Missing response from Hubspot API. Check the error logs.'));
    }

    $tokens = $this->oauthService->getTokensByCode($_GET['code']);
    $this->oauthService->saveTokens($tokens);
    return $this->redirect('hubspot_api.settings');
  }
}
