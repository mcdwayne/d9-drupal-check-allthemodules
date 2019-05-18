<?php

namespace Drupal\cloud\Plugin\views\field;

use Drupal\cloud\Plugin\CloudConfigPluginManagerInterface;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to present a link to an internal pricing.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("cloud_pricing_internal")
 */
class CloudPricingInternalLink extends LinkBase {

  /**
   * CloudConfigPlugin.
   *
   * @var \Drupal\cloud\Plugin\CloudConfigPluginManagerInterface
   */
  protected $cloudConfigPluginManager;

  /**
   * Constructs a LinkBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager.
   * @param \Drupal\cloud\Plugin\CloudConfigPluginManagerInterface $cloud_config_plugin_manager
   *   The cloud config plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccessManagerInterface $access_manager, CloudConfigPluginManagerInterface $cloud_config_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $access_manager);
    $this->cloudConfigPluginManager = $cloud_config_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('access_manager'),
      $container->get('plugin.manager.cloud_config_plugin')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    $this->cloudConfigPluginManager->setCloudContext($row->_entity->getCloudContext());
    $route = $this->cloudConfigPluginManager->getPricingPageRoute();
    return Url::fromRoute($route, ['cloud_context' => $row->_entity->getCloudContext()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('view');
  }

}
