<?php

namespace Drupal\league_oauth_login;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for league_oauth_login plugins.
 */
abstract class LeagueOauthLoginPluginBase extends PluginBase implements ContainerFactoryPluginInterface, LeagueOauthLoginInterface {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * LeagueOauthLoginPluginBase constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
  }

  /**
   * Default is no options.
   */
  public function getAuthUrlOptions() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getUserName(ResourceOwnerInterface $owner) {
    return $owner->getId();
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail(ResourceOwnerInterface $owner, $access_token) {
    return $owner->getEmail();
  }

}
