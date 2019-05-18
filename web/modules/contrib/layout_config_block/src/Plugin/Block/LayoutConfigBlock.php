<?php

namespace Drupal\layout_config_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Layout\LayoutPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a 'LayoutConfigBlock' block.
 *
 * @Block(
 *  id = "layout_config_block",
 *  admin_label = @Translation("Layout config block"),
 *  deriver = "Drupal\layout_config_block\Plugin\Derivative\LayoutConfigBlockDerivative"
 * )
 */
class LayoutConfigBlock extends BlockBase implements ContainerFactoryPluginInterface {


  protected $pluginManagerCoreLayout;
  protected $pluginManagerBlock;

  /**
   * Constructor to allow service injection.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Layout\LayoutPluginManager $pluginManagerCoreLayout
   *   The Layout manager service.
   * @param \Drupal\Core\Block\BlockManagerInterface $pluginManagerBlock
   *   The Block manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LayoutPluginManager $pluginManagerCoreLayout, BlockManagerInterface $pluginManagerBlock) {
    // Call parent construct method.
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Store our dependency.
    $this->pluginManagerCoreLayout = $pluginManagerCoreLayout;
    $this->pluginManagerBlock = $pluginManagerBlock;
  }

  /**
   * Create function to allow service injection.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container Interface.
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   *
   * @return static
   *   returns a container for injected services.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.core.layout'),
      $container->get('plugin.manager.block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Parse the config.
    $def = $this->getPluginDefinition();
    $config = $def['config'];
    $layout = $config->get("layout");
    $region_config = $config->get("regions");
    // Instantiate our layout plugin.
    $layoutInstance = $this->pluginManagerCoreLayout->createInstance($layout);
    // Cycle through config regions and build each block and place in
    // the region array.
    $regions = array_map(function ($block_ids) {
      return array_map(function ($block_id) {
        // We then get the instance of each plguin we need.
        $block_instance = $this->pluginManagerBlock->createInstance($block_id);
        return [
          '#theme' => 'block',
          '#attributes' => [],
          '#configuration' => $block_instance->getConfiguration(),
          '#plugin_id' => $block_instance->getPluginId(),
          '#base_plugin_id' => $block_instance->getBaseId(),
          '#derivative_plugin_id' => $block_instance->getDerivativeId(),
          'content' => $block_instance->build(),
        ];
      }, $block_ids);
    }, $region_config);
    // Return a build of our layout with regions.
    return $layoutInstance->build($regions);
  }

}
