<?php

namespace Drupal\menu_entity_index\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\menu_entity_index\TrackerInterface;
use Drupal\views\Plugin\views\filter\InOperator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter class which allows filtering by menu name.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("menu")
 */
class Menu extends InOperator {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Whether to return only tracked menus as options.
   *
   * @var bool
   */
  protected $trackedOnly;

  /**
   * The Menu Entity Index Tracker service.
   *
   * @var \Drupal\menu_entity_index\TrackerInterface
   */
  protected $tracker;

  /**
   * Constructs a Menu object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\menu_entity_index\TrackerInterface $tracker
   *   The Menu Entity Index Tracker service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, TrackerInterface $tracker) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->tracker = $tracker;
    $this->entityTypeManager = $entity_type_manager;
    $this->trackedOnly = isset($this->configuration['tracked_only']) ? $this->configuration['tracked_only'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('menu_entity_index.tracker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $options = $this->tracker->getAvailableMenus();
      if ($this->trackedOnly) {
        $tracked_menus = $this->tracker->getTrackedMenus();
        $options = array_filter($options, function ($key) use ($tracked_menus) {
          return in_array($key, $tracked_menus);
        }, ARRAY_FILTER_USE_KEY);
      }
      $this->valueOptions = $options;
    }

    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    // Add a cachable dependency on our configuration.
    if ($this->trackedOnly) {
      $dependencies['config'][] = 'menu_entity_index.configuration';
    }

    // Add a cachable dependency on each menu, that was selected.
    $menus = $this->entityTypeManager->getStorage('menu')->loadMultiple($this->value);
    foreach ($menus as $menu) {
      $dependencies[$menu->getConfigDependencyKey()][] = $menu->getConfigDependencyName();
    }

    return $dependencies;
  }

}
