<?php

namespace Drupal\assembly\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\views\Views;

/**
 * Base class for Assembly build plugins.
 */
abstract class AssemblyBuildView extends AssemblyBuildBase implements AssemblyBuildInterface {

  public function build(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    $arguments = $this->argumentMappings();
    $args_static = $this->arguments();
    $views = $this->views();
    foreach ($views as $key => $view_details) {
      $view_id = $view_details['view'];
      $display_id = isset($view_details['display']) ? $view_details['display'] : 'default';

      $args = $this->prepareArguments($key, $arguments, $entity);
      if (isset($args_static[$key])) {
        $args = $args_static[$key] + $args;
      }

      // Add the view to the build array
      $view = Views::getView($view_id);
      $view->setDisplay($display_id);
      $view->setArguments($args);
      $this->prepareView($view, $build, $entity, $display, $view_mode);
      $build[$key] = $view->buildRenderable();
      // If we have arguments we need to add them as cache keys
      if ($args) {
        $build[$key]['#cache']['keys'][] = serialize($args);
      }
      // Weight
      $build[$key]['#weight'] = isset($view_details['weight']) ? $view_details['weight'] : 99;

    }
  }

  protected function prepareArguments(string $key, array $arguments, EntityInterface $entity, bool $collapse = TRUE) {
    $args = [];
    // Build arguments
    foreach ($arguments as $field => $argument_info) {
      // If this is not an argument for this view...
      if (isset($argument_info['view']) && $argument_info['view'] != $key) {
        continue;
      }

      if (!$entity->hasField($field)) {
        \Drupal::logger('assembly')->error('Error in AssemblyBuildView plugin @plugin : argument specified field @field but that field was not found on the assembly of type @type', [
          '@plugin' => get_class($this),
          '@field' => $field,
          '@type' => $entity->bundle(),
        ]);
        continue;
      }

      $value = $entity->get($field)->getValue();
      $attribute = isset($argument_info['attribute']) ? $argument_info['attribute'] : 'target_id';

      $arg = '';
      if (!$value) {
        $args[$argument_info['index']] = isset($argument_info['all_value']) ? $argument_info['all_value'] : 'all';
      }

      $arg = [];
      foreach ($value as $item) {
        if (!isset($item[$attribute])) {
          \Drupal::logger('assembly')->error('Error in AssemblyBuildView plugin @plugin : argument specified attribute @attribute for field @field but that attribute was not found on a field value, assemblyl of type @type', [
            '@plugin' => get_class($this),
            '@field' => $field,
            '@attribute' => $attribute,
            '@type' => $entity->bundle(),
          ]);
          continue 2;
        }

        $args[$argument_info['index']][] = $item[$attribute];
      }

    }

    if ($collapse) {
      foreach ($args as $index => $arg) {
        if (!is_array($arg)) {
          continue;
        }
        foreach ($arguments as $info) {
          if ($info['index'] == $index) {
            $multiple = isset($info['multiple']) ? $info['multiple'] : 'or';
            $args[$index] = implode($multiple == 'or' ? '+' : ',', $arg);
            break;
          }
        }
      }
    }

    return $args;
  }

  /**
   * An associative array of field names to argument mapping settings.
   *
   * Takes the values provided by fields in the assembly and feeds them
   * to the view as contextual filters (arguments).
   *
   * By way of example:
   *  return [
   *    'field_categories' => [
   *      'index' => 0, // the argument index to which it. Required
   *      'view' => 'some_view_id', // the id of the view to which this gets assigned.
   *                                // Optional if only one view is assigned
   *      'attribute' => 'target_id', // the attribute for each field value to use. Defaults to target_id
   *      'multiple' => 'or', // or 'and'. Defaults to 'or'
   *      'all_value' => 'all', // Defaults to 'all'
   *    ],
   *  ];
   *
   * @return array The argument mappings
   */
  protected function argumentMappings() {
    return [];
  }

  /**
   * Provide a static list of arguments, keyed by the view key. This is merged
   * with arguments derived from argumentMappings if both are provided, with the
   * static arguments list taking precedence.
   *
   * Example:
   * return [
   *   'some_view_key' => [ 'arg1', 'arg2' ]
   * ]
   *
   * @return array
   */
  protected function arguments() {
    return [];
  }

  /**
   * Return a modified view object.
   *
   * Allows you to manipulate the view object before it gets passed to the builder.
   *
   * @return object View object
   */
  protected function prepareView($view, $build, $entity, $display, $view_mode) {
    return $view;
  }

  /**
   * Return a list of views that should get added to this view builder.
   *
   * Return value is an associative array structured like so:
   * return [
   *   'some_key' => [
   *     'view' => 'some_view_id',
   *     'display' => 'some_display_id' // optional, will use "default"
   *     'weight' => 99, // an optional weight value. defaults to 99
   *   ]
   * ];
   *
   * @return array The list of views
   */
  abstract protected function views();
}
