<?php
/**
 * @file
 * Contains \Drupal\widget_block\Plugin\Derivative\WidgetBlockDeriver.
 */

namespace Drupal\widget_block\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Retrieves block plugin definitions for all widget blocks.
 */
class WidgetBlockDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The widget block configuration storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $widgetBlockConfigStorage;

  /**
   * Create a WidgetBlockDeriver object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $widget_block_config_storage
   *   The widget block configuration storage.
   */
  public function __construct(EntityStorageInterface $widget_block_config_storage) {
    // Setup object members.
    $this->widgetBlockConfigStorage = $widget_block_config_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    /** @var $entity_manager \Drupal\Core\Entity\EntityManagerInterface */
    $entity_manager = $container->get('entity.manager');
    // Create a deriver instance based on the widget block configuration storage.
    return new static(
      $entity_manager->getStorage('widget_block_config')
    );
  }

  /**
   * Get the widget block configuration storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The widget block configuration storage.
   */
  protected function getWidgetBlockConfigStorage() {
    return $this->widgetBlockConfigStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    /** @var $widget_block_configs \Drupal\widget_block\Entity\WidgetBlockConfigInterface */
    $widget_block_configs = $this->getWidgetBlockConfigStorage()->loadMultiple();

    // Iterate through the loaded widget block configurations.
    foreach ($widget_block_configs as $widget_block_config) {
      // Get the widget block configuration. This matches with the Widget ID.
      $widget_id = $widget_block_config->id();
      // Declare the widget block derivative for the current $widget_block_config
      // based on the base plugin definition.
      $this->derivatives[$widget_id] = $base_plugin_definition;
      // Overwrite the administration label.
      $this->derivatives[$widget_id]['admin_label'] = $widget_block_config->label();
      // Set the Widget Block Configuration entity as a dependency.
      $this->derivatives[$widget_id]['config_dependencies']['config'] = [
        $widget_block_config->getConfigDependencyName(),
      ];
    }
    // Perform default operation for given base plugin definition.
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
