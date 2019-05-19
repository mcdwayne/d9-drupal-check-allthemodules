<?php

namespace Drupal\whitelabel\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Drupal\whitelabel\WhiteLabelProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default argument plugin to extract the current uid from a given white label.
 *
 * This plugin can be used to populate a user id field from a white label.
 *
 * @ViewsArgumentDefault(
 *   id = "current_white_label_uid",
 *   title = @Translation("User ID associated with active White Label")
 * )
 */
class CurrentWhiteLabelUid extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * Holds the white label provider.
   *
   * @var \Drupal\whitelabel\WhiteLabelProviderInterface
   */
  protected $whiteLabelProvider;

  /**
   * Constructs a new Node instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\whitelabel\WhiteLabelProviderInterface $white_label_provider
   *   The white label provider
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WhiteLabelProviderInterface $white_label_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->whiteLabelProvider = $white_label_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('whitelabel.whitelabel_provider'));
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    // If there is a whitelabel, return its owner.
    $whitelabel = $this->whiteLabelProvider->getWhiteLabel();
    return !empty($whitelabel) ? $whitelabel->getOwnerId() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['whitelabel'];
  }

}
