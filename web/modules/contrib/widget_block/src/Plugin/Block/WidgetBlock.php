<?php
/**
 * @file
 * Contains \Drupal\widget_block\Plugin\Block\WidgetBlock.
 */

namespace Drupal\widget_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\widget_block\Backend\WidgetBlockBackendInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a generic block for embedding widgets.
 *
 * @Block(
 *  id = "widget_block",
 *  admin_label = @Translation("Widget Block"),
 *  category = @Translation("Widget Block"),
 *  deriver = "Drupal\widget_block\Plugin\Derivative\WidgetBlockDeriver"
 * )
 */
class WidgetBlock extends BlockBase implements WidgetBlockInterface, ContainerFactoryPluginInterface {

  /**
   * The widget block configuration storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $widgetBlockConfigStorage;

  /**
   * The widget service backend.
   *
   * @var \Drupal\widget_block\Backend\WidgetBlockBackendInterface
   */
  protected $backend;

  /**
   * The widget block configuration entity.
   *
   * @var \Drupal\widget_block\Entity\WidgetBlockConfigInterface|NULL
   */
  protected $configEntity;

  /**
   * Create a WidgetBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\widget_block\Backend\WidgetBlockBackendInterface $backend
   *   The widget backend service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $widget_block_config_storage
   *   The widget block configuration storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WidgetBlockBackendInterface $backend, EntityStorageInterface $widget_block_config_storage) {
    // Setup object members.
    $this->backend = $backend;
    $this->widgetBlockConfigStorage = $widget_block_config_storage;
    // Perform default object construction.
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var $entity_manager \Drupal\Core\Entity\EntityManagerInterface */
    $entity_manager = $container->get('entity.manager');
    // Create a block instance using the widget block configuration storage.
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('widget_block.backend'),
      $entity_manager->getStorage('widget_block_config')
    );
  }

  /**
   * Get the widget block backend.
   *
   * @return \Drupal\widget_block\Backend\WidgetBlockBackendInterface
   *   An instance of WidgetBlockBackendInterface.
   */
  protected function getBackend() {
    return $this->backend;
  }

  /**
   * Get the widget block configuration storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The widget block configuration storage.
   */
  protected function getStorage() {
    return $this->widgetBlockConfigStorage;
  }

  /**
   * Get the configuration entity.
   *
   * @return \Drupal\widget_block\Entity\WidgetBlockConfigInterface|NULL
   *   An instance of WidgetBlockConfigInterface.
   */
  public function getConfigEntity() {
    // Check whether the configuration entity needs to be resolved.
    if ($this->configEntity === NULL) {
      // Load the configuration entity using the derivative ID.
      $this->configEntity = $this->getStorage()->load($this->getDerivativeId());
    }

    return $this->configEntity;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate(LanguageInterface $language) {
    // Get the configuration entity if available.
    if (($config = $this->getConfigEntity())) {
      // Instruct our backend to invalidate markup for given configuration
      // and language.
      return $this->getBackend()->invalidate($config, $language);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function refresh(LanguageInterface $language, $forced = FALSE) {
    // Get the configuration entity if available.
    if (($config = $this->getConfigEntity())) {
      // Instruct our backend to refresh markup for given configuration
      // and language.
      return $this->getBackend()->refresh($config, $language, $forced);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Initialize $build variable to an empty array as default behavior.
    $build = [];

    // Get the widget block configuration entity.
    if (($config_entity = $this->getConfigEntity()) !== NULL) {
      // Get the widget block configuration identifier.
      $widget_block_config_id = $config_entity->id();
      // Set the lazy builder service.
      $build['#lazy_builder'] = ['widget_block.lazy_builder:build', [$widget_block_config_id]];
    }

    return $build;
  }

}
