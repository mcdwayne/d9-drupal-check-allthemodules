<?php

namespace Drupal\social_auth_strava\Plugin\Network;

use Drupal\social_auth\Plugin\Network\SocialAuthNetwork;
use Strava\API\OAuth;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\social_api\SocialApiException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Defines Social Auth Strava Network Plugin.
 *
 * @Network(
 *   id = "social_auth_strava",
 *   social_network = "Strava",
 *   type = "social_auth",
 *   handlers = {
 *      "settings": {
 *          "class": "\Drupal\social_auth_strava\Settings\StravaAuthSettings",
 *          "config_id": "social_auth_strava.settings"
 *      }
 *   }
 * )
 */
class StravaAuth extends SocialAuthNetwork {

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Render\MetadataBubblingUrlGenerator
   */
  protected $urlGenerator;

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
   * StravaLogin constructor.
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
  public function __construct(MetadataBubblingUrlGenerator $url_generator, array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $config_factory);

    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function initSdk() {

    $class_name = 'Strava\API\OAuth';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The PHP SDK for Strava could not be found. Class: %s.', $class_name));
    }

    /* @var \Drupal\social_auth_strava\Settings\StravaAuthSettings $settings */
    $settings = $this->settings;

    $redirect_uri = $this->urlGenerator->generateFromRoute('social_auth_strava.callback', array(), array('absolute' => TRUE));

    $options = [
      'clientId'     => $settings->getClientId(),
      'clientSecret' => $settings->getClientSecret(),
      'redirectUri'  => $redirect_uri,
    ];
    $client = new OAuth($options);

    return $client;
  }
}