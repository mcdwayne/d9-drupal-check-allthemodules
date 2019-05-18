<?php

namespace Drupal\menu_entity_index\Plugin\views\field;

use Drupal\menu_entity_index\TrackerInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field plugin for target type field.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("menu_entity_index_target_type")
 */
class TargetType extends FieldPluginBase {

  /**
   * The Menu Entity Index Tracker service.
   *
   * @var \Drupal\menu_entity_index\TrackerInterface
   */
  protected $tracker;

  /**
   * Constructs a PluginBase object.
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

    $this->definition = $plugin_definition + $configuration;
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
  public function render(ResultRow $values) {
    $available_menus = $this->tracker->getAvailableEntityTypes();
    $value = $this->getValue($values);
    $value = isset($available_menus[$value]) ? $available_menus[$value] : $value;
    return $this->sanitizeValue($value);
  }

}
