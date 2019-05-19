<?php
/**
 * @file
 * Theme file for the visualization part of the module.
 */

use Drupal\Core\Template\Attribute;

/**
 * Preprocess function for visualization function.
 *
 * Used by both regular calls to theme() and the visualization Views handler.
 */
function template_preprocess_visualization(&$vars) {
  if (!empty($vars['view'])) {
    $view = $vars['view'];
    $options = $view->style_plugin->options;

    // Fields.
    foreach ($view->field as $name => $field) {
      $options['fields'][$name]['label'] = $field->options['label'];
    }

    // Title.
    if (empty($options['title'])) {
      $options['title'] = $view->getTitle();
    }

    // Data.
    $data = $view->style_plugin->get_render_fields();
  }
  else {
    $options = $vars['options'];
    $data = $options['data'];
  }

  if (!empty($options['xAxis']['invert'])) {
    $data = array_reverse($data);
  }

  $chart_id = 'visualization_' . uniqid();

  $plugin = visualization_plugin($options['type']);
  $vars['chart'] = $plugin->render($chart_id, $data, $options);

  $vars['chart_attributes'] = new Attribute(
    array(
      'id' => $chart_id,
      'class' => array(
        'visualization-chart',
        'visualization-chart-' . $plugin->name,
      ),
    )
  );

  $plugin->postRender();
}
