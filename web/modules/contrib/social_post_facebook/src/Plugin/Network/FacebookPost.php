<?php

namespace Drupal\social_post_facebook\Plugin\Network;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Drupal\social_api\SocialApiException;
use Drupal\social_post\Plugin\Network\SocialPostNetwork;
use Drupal\social_post_facebook\Settings\FacebookPostSettings;
use Facebook\Facebook;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines Social Post Facebook Network Plugin.
 *
 * @Network(
 *   id = "social_post_facebook",
 *   social_network = "Facebook",
 *   type = "social_post",
 *   handlers = {
 *     "settings": {
 *        "class": "\Drupal\social_post_facebook\Settings\FacebookPostSettings",
 *        "config_id": "social_post_facebook.settings"
 *      }
 *   }
 * )
 */
class FacebookPost extends SocialPostNetwork implements FacebookPostInterface {

  use LoggerChannelTrait;

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Render\MetadataBubblingUrlGenerator
   */
  protected $urlGenerator;

  /**
   * Facebook connection.
   *
   * @var \Facebook\Facebook
   */
  protected $connection;

  /**
   * The status text.
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
   * User Access token.
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
      $container->get('config.factory')
    );
  }

  /**
   * FacebookPost constructor.
   *
   * @param \Drupal\Core\Render\MetadataBubblingUrlGenerator $url_generator
   *   Used to generate a absolute url for authentication.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(MetadataBubblingUrlGenerator $url_generator,
                              array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager,
                              ConfigFactoryInterface $config_factory) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $config_factory);

    $this->urlGenerator = $url_generator;
  }

  /**
   * Sets the underlying SDK library.
   *
   * @return \Facebook\Facebook
   *   The initialized 3rd party library instance.
   *
   * @throws SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {
    $class_name = '\Facebook\Facebook';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The Facebook SDK not found. Class: %s.', $class_name));
    }

    /** @var \Drupal\social_post_facebook\Settings\FacebookPostSettings $settings */
    $settings = $this->settings;
    if ($this->validateConfig($settings)) {
      throw new SocialApiException('The Social Facebook Configuration is not valid');
    }

    return new Facebook([
      'app_id' => $settings->getAppId(),
      'app_secret' => $settings->getAppSecret(),
      'default_graph_version' => 'v' . $settings->getGraphVersion(),
    ]);
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_post_facebook\Settings\FacebookPostSettings $settings
   *   The Facebook auth settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(FacebookPostSettings $settings) {
    $app_id = $settings->getAppId();
    $app_secret = $settings->getAppSecret();
    $graph_version = $settings->getGraphVersion();
    if (!$app_id || !$app_secret || !$graph_version) {
      $this->loggerFactory
        ->get('social_auth_facebook')
        ->error('Define App ID and App Secret on module settings.');
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function post() {
    if (!$this->connection) {
      throw new SocialApiException('Call post() method from its wrapper doPost()');
    }

    $message = $this->connection->post('me/feed', ['message' => $this->status], $this->accessToken);

    if ($message->getHttpStatusCode() != 200) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function doPost($access_token, $status) {
    $this->connection = $this->getSdk();
    $this->status = $status;
    $this->accessToken = $access_token;
    return $this->post();
  }

  /**
   * {@inheritdoc}
   */
  public function getOauthCallback() {
    return $this->urlGenerator->generateFromRoute('social_post_facebook.callback', [], ['absolute' => TRUE]);
  }

}
