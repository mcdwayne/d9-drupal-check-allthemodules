<?php

namespace Drupal\stacks\Plugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Class WidgetTypeBase.
 * @package Drupal\stacks\Plugin
 */
abstract class WidgetTypeBase extends PluginBase implements WidgetTypeInterface {

  /**
   * Which template file are we loading. In the case of the content feed, this
   * is also used to load the correct ajax template file.
   */
  protected $template;

  /**
   * This holds all other options available for grids.
   */
  protected $grid_options = [];

  /**
   * If this grid is connected to a stacks entity, include that here.
   */
  protected $widget_entity = FALSE;

  /**
   * A unique identifier for this grid widget.
   */
  protected $unique_id = FALSE;

  /**
   * Sets up the grid options.
   */
  function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setEntity($configuration['widget_entity']);
  }

  /**
   * Define the fields that should not be sent to the template as variables.
   * These are usually fields on the bundle that you want to handle via
   * programming only.
   */
  public function fieldExceptions() {
    return [];
  }

  /**
   * Modify the render array before output. This is meant to be overridden by
   * child classes.
   *
   * @param array $render_array Modify the variables sent to the template.
   * @param array $options Array of options.
   *    'active_filters' array Sets options filters for the query.
   *    'is_ajax' bool Is this an ajax request?
   */
  public function modifyRenderArray(&$render_array, $options = []) {
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    $this->widget_entity = $entity;
    $this->unique_id = $entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function setNoneEntity($unique_id) {
    $this->unique_id = (int) $unique_id;
  }

}
