<?php

namespace Drupal\social_post_linkedin\Plugin\Network;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Drupal\social_api\SocialApiException;
use Drupal\social_post\Plugin\Network\SocialPostNetwork;
use Drupal\social_post_linkedin\Settings\LinkedInPostSettings;
use League\OAuth2\Client\Provider\LinkedIn;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines Social Post LinkedIn Network Plugin.
 *
 * @Network(
 *   id = "social_post_linkedin",
 *   social_network = "LinkedIn",
 *   type = "social_post",
 *   handlers = {
 *     "settings": {
 *        "class": "\Drupal\social_post_linkedin\Settings\LinkedInPostSettings",
 *        "config_id": "social_post_linkedin.settings"
 *      }
 *   }
 * )
 */
class LinkedInPost extends SocialPostNetwork {

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Render\MetadataBubblingUrlGenerator
   */
  protected $urlGenerator;

  /**
   * LinkedIn connection.
   *
   * @var \League\OAuth2\Client\Provider\LinkedIn
   */
  protected $client;

  /**
   * The Post text.
   *
   * @var string
   */
  protected $status;

  /**
   * The logger factory object.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * The access token.
   *
   * @var string
   */
  protected $accessToken;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('url_generator'),
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * LinkedInPost constructor.
   *
   * @param \Drupal\Core\Render\MetadataBubblingUrlGenerator $url_generator
   *   Used to generate a absolute url for authentication.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   Used for logging errors.
   */
  public function __construct(MetadataBubblingUrlGenerator $url_generator,
                              array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager,
                              ConfigFactoryInterface $config_factory,
                              LoggerChannelFactory $logger_factory) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $config_factory);

    $this->urlGenerator = $url_generator;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Sets the underlying SDK library.
   *
   * @return \League\OAuth2\Client\Provider\LinkedIn
   *   The initialized 3rd party library instance.
   *
   * @throws SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {

    $class_name = '\League\OAuth2\Client\Provider\LinkedIn';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The LinkedIn library for the PHP League OAuth2 Client not found. Class: %s.', $class_name));
    }
    /* @var \Drupal\social_post_linkedin\Settings\LinkedInPostSettings $settings */
    $settings = $this->settings;
    if ($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $league_settings = [
        'clientId' => $settings->getClientId(),
        'clientSecret' => $settings->getClientSecret(),
        'redirectUri' => $GLOBALS['base_url'] . '/user/social-post/linkedin/auth/callback',
      ];

      return new LinkedIn($league_settings);
    }
    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_post_linkedin\Settings\LinkedInPostSettings $settings
   *   The Social Post LinkedIn settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(LinkedInPostSettings $settings) {
    $client_id = $settings->getClientId();
    $client_secret = $settings->getClientSecret();
    if (!$client_id || !$client_secret) {
      $this->loggerFactory
        ->get('social_post_linkedin')
        ->error('Define Client ID and Client Secret on module settings.');
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function post() {
    if (!$this->client) {
      throw new SocialApiException('Call post() method from its wrapper doPost()');
    }

    /* @var \Psr\Http\Message\RequestInterface $request */
    $request = $this->client->getAuthenticatedRequest(
      'POST',
      'https://api.linkedin.com/v1/people/~/shares?format=json',
      $this->accessToken
    );

    $body = \GuzzleHttp\Psr7\stream_for($this->status);

    $request = $request->withAddedHeader('Content-Type', 'application/json')
      ->withAddedHeader('x-li-format', 'json')
      ->withBody($body);

    $response = $this->client->getResponse($request);

    if ($response->getStatusCode() !== 201) {
      $this->loggerFactory->get('social_post_linkedin')->error($response->getBody()->__toString());
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function doPost($access_token, $status) {
    $this->accessToken = $access_token;
    $this->status = $status;
    $this->client = $this->getSdk();

    return $this->post();
  }

}
