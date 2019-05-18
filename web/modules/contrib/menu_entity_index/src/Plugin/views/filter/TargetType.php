<?php

namespace Drupal\menu_entity_index\Plugin\views\filter;

use Drupal\menu_entity_index\TrackerInterface;
use Drupal\views\Plugin\views\filter\InOperator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter class which allows filtering by entity type id of target entity.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("menu_entity_index_target_type")
 */
class TargetType extends InOperator {

  /**
   * The Menu Entity Index Tracker service.
   *
   * @var \Drupal\menu_entity_index\TrackerInterface
   */
  protected $tracker;

  /**
   * Constructs a Bundle object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\menu_entity_index\TrackerInterface $tracker
   *   The Menu Entity Index Tracker service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TrackerInterface $tracker) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->tracker = $tracker;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu_entity_index.tracker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $options = $this->tracker->getAvailableEntityTypes();
      $tracked_types = $this->tracker->getTrackedEntityTypes();
      $options = array_filter($options, function ($key) use ($tracked_types) {
        return in_array($key, $tracked_types);
      }, ARRAY_FILTER_USE_KEY);
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

    return $dependencies;
  }

}
