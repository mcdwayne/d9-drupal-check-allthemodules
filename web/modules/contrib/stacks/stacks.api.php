<?php
/**
 * @file
 * Documentation for the hooks provided by this module.
 */

/**
 * Modify the render array value before it is output to the screen. This is
 * only meant for none custom widget types. For custom widget types, create the
 * method modifyRenderArray() in the grid class.
 *
 * @param $render_array
 * @param $entity ($node)
 * @param $widget_entity
 */
function hook_stacks_output_alter(&$render_array, $entity, $widget_entity) {}

/**
 * Modify the query that returns the node results.
 *
 * @param $query: The query object that you can modify.
 * @param $group: The group condition for the filters.
 * @param $context: Contains 'widget_bundle' (the bundle of the widget and
 * 'options', which are the options that are sent to the query builder function.
 */
function hook_widget_node_results_alter(&$query, $group, &$context) {}

/**
 * Allows modules to prevent a specfic widget for being displayed
 *
 * @param Entity $entity: The entity that holds the widget
 * @param WidgetInstanceEntity $widget_instance_entity: The container entity that referecne the widget
 * @param WidgetEntity $widget_instance: The actual widget entity

 * @return array:
 *  'skip': If true, the widget not be displayed
 */
function hook_stacks_pre_output($entity, WidgetInstanceEntity $widget_instance_entity, WidgetEntity $widget_entity) {
  $entity = $args['entity'];

  // Perform some validations.
  $result['skip'] = TRUE;

  return $result;
}

