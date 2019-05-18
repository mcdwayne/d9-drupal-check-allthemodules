<?php

namespace Drupal\eloqua_app_cloud\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\eloqua_rest_api\Factory\ClientFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Eloqua AppCloud Menu Responder plugins.
 */
abstract class EloquaAppCloudResponderBase extends PluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var ClientFactory
   */
  protected $eloqua;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientFactory $eloqua) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->eloqua = $eloqua;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eloqua.client_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    $this->pluginDefinition;
  }

}
