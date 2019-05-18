<?php

namespace Drupal\pdb_vue\Plugin\Derivative;

use Drupal\pdb\Plugin\Derivative\PdbBlockDeriver;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\pdb\ComponentDiscoveryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derives block plugin definitions for Vue components.
 */
class VueBlockDeriver extends PdbBlockDeriver {

  /**
   * Config Factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * VueBlockDeriver constructor.
   *
   * @param \Drupal\pdb\ComponentDiscoveryInterface $component_discovery
   *   The component discovery service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory service.
   */
  public function __construct(ComponentDiscoveryInterface $component_discovery, ConfigFactoryInterface $config_factory) {
    parent::__construct($component_discovery);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('pdb.component_discovery'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $definitions = parent::getDerivativeDefinitions($base_plugin_definition);

    // Remove any demo components if development mode is not enabled.
    $config_settings = $this->configFactory->get('pdb_vue.settings');
    if (isset($config_settings) && !$config_settings->get('development_mode')) {
      $demos = [
        'ng2_example_1',
        'ng2_example_2',
        'ng2_example_configuration',
        'ng2_example_node',
        'ng2_hero',
        'ng2_todo',
        'react_example_1',
        'react_todo',
        'vue_example_1',
        'vue_example_2',
        'vue_example_webpack',
        'vue_todo',
      ];

      foreach ($definitions as $key => $definition) {
        if (in_array($key, $demos)) {
          unset($definitions[$key]);
        }
      }
    }

    return array_filter($definitions, function (array $definition) {
      return $definition['info']['presentation'] == 'vue';
    });
  }

}
