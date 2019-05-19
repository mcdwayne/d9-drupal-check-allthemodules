<?php

namespace Drupal\social_auth_itsme\Plugin\Network;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\social_api\Plugin\NetworkBase;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth_itsme\Settings\ItsmeAuthSettings;
use Nascom\ItsmeApiClient\Http\ApiClient\ApiClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Site\Settings;
use GuzzleHttp\Client;

/**
 * Defines a Network Plugin for Social Auth itsme.
 *
 * @package Drupal\social_auth_itsme\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_itsme",
 *   social_network = "itsme",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_itsme\Settings\ItsmeAuthSettings",
 *       "config_id": "social_auth_itsme.settings"
 *     }
 *   }
 * )
 */
class ItsmeAuth extends NetworkBase implements ItsmeAuthInterface {

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
   * ItsmeAuth constructor.
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
                              Settings $settings
  ) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $config_factory);

    $this->dataHandler = $data_handler;
    $this->loggerFactory = $logger_factory;
    $this->requestContext = $requestContext;
    $this->siteSettings = $settings;
  }

  /**
   * Sets the underlying SDK library.
   *
   * @return \Nascom\ItsmeApiClient\Http\ApiClient\ApiClient
   *   The initialized 3rd party library instance.
   *
   * @throws SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {
    $class_name = '\Nascom\ItsmeApiClient\Http\ApiClient\ApiClient';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The itsme library for PHP not found. Class: %s.', $class_name));
    }

    if ($this->validateConfig($this->getSettings())) {
      // All these settings are mandatory.
      $guzzle_settings = [
        'base_uri' => $this->getSettings()->getService(),
      ];

      // Proxy configuration data for outward proxy.
      if ($proxyUrl = $this->siteSettings->get("http_client_config")["proxy"]["http"]) {
        $guzzle_settings['proxy'] = $proxyUrl;
      }

      $guzzleClient = new Client($guzzle_settings);
      return new ApiClient($guzzleClient);
    }

    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth_itsme\Settings\ItsmeAuthSettings $settings
   *   The itsme auth settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(ItsmeAuthSettings $settings) {
    $token = $settings->getToken();
    $service = $settings->getService();
    if (!$token || !$service) {
      $this->loggerFactory
        ->get('social_auth_itsme')
        ->error('Define token and service in module settings.');
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Gets the settings.
   *
   * @return \Drupal\social_api\Settings\SettingsInterface
   *   Settings
   */
  public function getSettings() {
    return $this->settings;
  }

}
