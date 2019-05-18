<?php

namespace Drupal\reference_table_formatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * A service for turning entities into tables.
 */
class EntityToTableRenderer implements EntityToTableRendererInterface {

  /**
   * Create an instance of the table renderer.
   */
  public function __construct(EntityManagerInterface $entity_manager, RendererInterface $renderer) {
    $this->entityManager = $entity_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function getTable($type, $bundle, $entities, $settings) {

    $table_rows = $this->getPreparedRenderedEntities($type, $bundle, $entities, $settings);
    $table_columns = $this->getTableColumns($table_rows);

    $table = [
      '#theme' => 'table',
    ];

    if (!$settings['hide_header']) {
      $table['#header'] = $table_columns;
    }

    foreach ($table_rows as $table_row) {
      $row = &$table['#rows'][];
      foreach ($table_columns as $field_name => $field_title) {
        // If the field we need to render exists on the entity, render it
        // without a title, otherwise fill the cell anyway with a default.
        if (isset($table_row[$field_name])) {
          $table_row[$field_name]['#label_display'] = 'hidden';
          $row[] = $this->renderer->render($table_row[$field_name]);
        }
        else {
          $row[] = $settings['empty_cell_value'];
        }
      }
    }

    $cache_metadata = new CacheableMetadata();
    foreach ($entities as $entity) {
      $cache_metadata->addCacheableDependency($entity);
      $cache_metadata->addCacheableDependency($entity->access('view', NULL, TRUE));
    }
    $cache_metadata->applyTo($table);

    return $table;
  }

  /**
   * Prepare all of the given entities for rendering with applicable fields.
   *
   * @param string $type
   *   The entity type of the given entities.
   * @param string $bundle
   *   The bundle that the entities are composed of.
   * @param array $entities
   *   An array of entities to put into the table.
   * @param array $settings
   *   The settings array from the field formatter base containing keys:
   *     - view_mode: The target view mode to render the field settings from.
   *     - show_entity_label: If we should display the entity label.
   *     - empty_cell_value: The value to show in empty cells.
   *
   * @return array
   *   An array of entities with applicable fields prepared for rendering.
   */
  protected function getPreparedRenderedEntities($type, $bundle, $entities, $settings) {
    // Build, sort and filter the entity fields to ensure the weight is
    // respected and we only show fields which are relevant or have been
    // configured for the table.
    $filtered_table_entities = [];
    $display_renderer = $this->getDisplayRenderer($type, $bundle, $settings['view_mode']);
    foreach ($display_renderer->buildMultiple($entities) as $table_entity) {
      // Filter out fields which we don't want to render.
      $filtered_entity = array_filter($table_entity, [$this, 'fieldIsRenderableContent']);
      // If we are showing the entity label, add it to the fields list.
      if ($settings['show_entity_label']) {
        $label_field_key = $this->entityManager->getDefinition($type)->getKey('label');
        $filtered_entity[$label_field_key] = $table_entity[$label_field_key];
      }
      // Sort the fields by weight.
      uasort($filtered_entity, ['\Drupal\Component\Utility\SortArray', 'sortByWeightProperty']);
      $filtered_table_entities[] = $filtered_entity;
    }
    return $filtered_table_entities;
  }

  /**
   * Get the display renderer.
   *
   * @param string $type
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   * @param string $view_mode
   *   The view mode.
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   *   The display renderer.
   */
  protected function getDisplayRenderer($type, $bundle, $view_mode) {
    // For entities with no bundles, the bundle type always defaults to the
    // entity type. This is a hardcoded constraint made in core,
    // see ContentEntityBase::__construct.
    $bundle = $bundle ?: $type;
    $storage = $this->entityManager->getStorage('entity_view_display');
    // When a display renderer doesn't exist, fall back to the default.
    $renderer = $storage->load(implode('.', [$type, $bundle, $view_mode]));
    if (!$renderer) {
      $renderer = $storage->load(implode('.', [$type, $bundle, 'default']));
    }
    return $renderer;
  }

  /**
   * Get the fields which will appear in the table.
   *
   * @param array $rendered_entities
   *   All of the entities which will be shown on the table.
   *
   * @return array
   *   The fields to render keyed by name with the title as the value.
   */
  protected function getTableColumns($rendered_entities) {
    $element_counts = array_map('count', $rendered_entities);
    $max_count_keys = array_keys($element_counts, max($element_counts));
    $entity_with_most_fields = $rendered_entities[array_shift($max_count_keys)];
    $table_fields = [];
    foreach ($entity_with_most_fields as $field) {
      $table_fields[$field['#field_name']] = $field['#title'];
    }
    return $table_fields;
  }

  /**
   * A helper to check if we should display this field.
   *
   * @param array $field
   *   The field information.
   *
   * @return bool
   *   TRUE if we should display the field otherwise FALSE.
   */
  protected function fieldIsRenderableContent($field) {
    return isset($field['#items']) && $field['#items']->getFieldDefinition()->isDisplayConfigurable('view');
  }

}
