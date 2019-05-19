<?php

namespace Drupal\kpi_analytics;

use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class KPIBuilder.
 *
 * @package Drupal\kpi_analytics
 */
class KPIBuilder implements KPIBuilderInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var Drupal\Core\Entity\EntityTypeManager
   */
  protected $entity_type_manager;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entity_type_manager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function build($entity_type_id, $entity_id) {
    /** @var \Drupal\block_content\Entity\BlockContent $entity */
    $entity = $this->entity_type_manager->getStorage($entity_type_id)
      ->load($entity_id);
    $query = $entity->field_kpi_query->value;
    $datasource = $entity->field_kpi_datasource->value;
    $datasource_plugin = \Drupal::service('plugin.manager.kpi_datasource.processor')
      ->createInstance($datasource);
    $data = $datasource_plugin->query($query);

    $data_formatters = $entity->field_kpi_data_formatter->getValue();
    foreach ($data_formatters as $data_formatter) {
      $data_formatter_plugin = \Drupal::service('plugin.manager.kpi_data_formatter.processor')
        ->createInstance($data_formatter['value']);
      $data = $data_formatter_plugin->format($data);
    }

    $visualization = $entity->field_kpi_visualization->value;
    // Retrieve the plugins.
    $visualization_plugin = \Drupal::service('plugin.manager.kpi_visualization.processor')
      ->createInstance($visualization);

    $labels = array_map(function ($item) {
      return $item['value'];
    }, $entity->get('field_kpi_chart_labels')->getValue());

    $colors = array_map(function ($item) {
      return $item['value'];
    }, $entity->get('field_kpi_chart_colors')->getValue());

    $render_array = $visualization_plugin
      ->setLabels($labels)
      ->setColors($colors)
      ->render($data);

    return $render_array;
  }
}

