<?php

namespace Drupal\media_entity_consent\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\CachedStorage;

/**
 * Provides a 'ConsentConfigBlock' block.
 *
 * @Block(
 *  id = "media_entity_consent_user_settings_block",
 *  admin_label = @Translation("Media entity consent user settings form"),
 * )
 */
class ConsentConfigBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Config\CachedStorage definition.
   *
   * @var \Drupal\Core\Config\CachedStorage
   */
  protected $configStorage;

  /**
   * Constructs a new ConsentConfigBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\CachedStorage $config_storage
   *   The config storage.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    CachedStorage $config_storage
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configStorage = $config_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['consent_form'] = \Drupal::formBuilder()
      ->getForm('Drupal\media_entity_consent\Form\MediaEntityConsentUserSettingsForm');
    $config = \Drupal::config('media_entity_consent.settings');
    $build['#cache']['tags'] = $config->getCacheTags();
    $build['#cache']['contexts'][] = 'user.roles';
    $build['#cache']['contexts'][] = 'cookies';
    return $build;
  }

}
