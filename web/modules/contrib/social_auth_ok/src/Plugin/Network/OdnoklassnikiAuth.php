<?php

namespace Drupal\social_auth_ok\Plugin\Network;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\social_api\Plugin\NetworkBase;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth_ok\Settings\OdnoklassnikiAuthSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Max107\OAuth2\Client\Provider\Odnoklassniki;
use Drupal\Core\Site\Settings;

/**
 * Defines a Network Plugin for Social Auth Odnoklassniki.
 *
 * @Network(
 *   id = "social_auth_ok",
 *   social_network = "Odnoklassniki",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_ok\Settings\OdnoklassnikiAuthSettings",
 *       "config_id": "social_auth_ok.settings"
 *     }
 *   }
 * )
 */
class OdnoklassnikiAuth extends NetworkBase implements OdnoklassnikiAuthInterface  {

  /**
   * The Social Auth Data Handler.
   *
   * @var \Drupal\social_auth\SocialAuthDataHandler
   */
  protected $dataHandler;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * The request context object.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * The site settings.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $siteSettings;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('social_auth.data_handler'),
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('router.request_context'),
      $container->get('settings')
    );
  }


  /**
   * OdnoklassnikiAuth constructor.
   *
   * @param \Drupal\social_auth\SocialAuthDataHandler $data_handler
   *   The data handler.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Routing\RequestContext $requestContext
   *   The Request Context Object.
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings factory.
   */
  public function __construct(SocialAuthDataHandler $data_handler,
                              array $configuration,
                              $plugin_id,
                              array $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager,
                              ConfigFactoryInterface $config_factory,
                              LoggerChannelFactoryInterface $logger_factory,
                              RequestContext $requestContext,
                              Settings $settings) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $config_factory);

    $this->dataHandler = $data_handler;
    $this->loggerFactory = $logger_factory;
    $this->requestContext = $requestContext;
    $this->siteSettings = $settings;
  }


  /**
   * Sets the underlying SDK library.
   *
   * @return false|Odnoklassniki
   *   The initialized 3rd party library instance.
   *
   * @throws SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {

    $class_name = '\Max107\OAuth2\Client\Provider\Odnoklassniki';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The OAuth2 library for Odnoklassniki not found. Class: %s.', $class_name));
    }
    /* @var \Drupal\social_auth_ok\Settings\OdnoklassnikiAuthSettings $settings */
    $settings = $this->settings;

    if ($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $ok_provider_settings = [
        'clientId'          => $settings->getClientId(),
        'clientPublic'      => $settings->getClientPublic(),
        'clientSecret'      => $settings->getClientSecret(),
        'redirectUri'       => $this->requestContext->getCompleteBaseUrl() . '/user/login/ok/callback',
      ];

      // Proxy configuration data for outward proxy.
      $proxyUrl = $this->siteSettings->get('http_client_config')['proxy']['http'];
      if ($proxyUrl) {
        $ok_provider_settings = [
          'proxy' => $proxyUrl,
        ];
      }

      return new Odnoklassniki($ok_provider_settings);
    }
    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param OdnoklassnikiAuthSettings $settings
   *   The Odnoklassniki auth settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(OdnoklassnikiAuthSettings $settings) {
    $client_id = $settings->getClientId();
    $client_public = $settings->getClientPublic();
    $client_secret = $settings->getClientSecret();

    if (!$client_id || !$client_public || !$client_secret) {
      $this->loggerFactory
        ->get('social_auth_ok')
        ->error('Define Client ID, Client Public and Client Secret on module settings.');
      return FALSE;
    }

    return TRUE;
  }

}
