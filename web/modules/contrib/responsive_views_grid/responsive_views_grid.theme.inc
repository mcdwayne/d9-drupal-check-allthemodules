<?php

/**
 * @file
 * Preprocess function for themeing.
 */

use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for views grid style templates.
 *
 * Default template: views-view-responsive-grid.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: The view object.
 *   - rows: An array of row items. Each row is an array of content.
 */
function template_preprocess_views_view_responsive_grid(array &$variables) {
  $variables['#attached']['library'][] = 'responsive_views_grid/responsive_views_grid';
  $options = $variables['options'] = $variables['view']->style_plugin->options;
  $horizontal = ($options['alignment'] === 'horizontal');

  $col = 0;
  $row = 0;
  $items = [];
  $remainders = count($variables['rows']) % $options['columns'];
  $num_rows = floor(count($variables['rows']) / $options['columns']);

  // Iterate over each rendered views result row.
  foreach ($variables['rows'] as $result_index => $item) {

    // Add the item.
    if ($horizontal) {
      $items[$row]['content'][$col]['content'] = $item;
    }
    else {
      $items[$col]['content'][$row]['content'] = $item;
    }

    // Create attributes for rows.
    if (!$horizontal || ($horizontal && empty($items[$row]['attributes']))) {
      $row_attributes = ['class' => []];
      // Add custom row classes.
      $row_class = array_filter(explode(' ', $variables['view']->style_plugin->getCustomClass($result_index, 'row')));
      if (!empty($row_class)) {
        $row_attributes['class'] = array_merge($row_attributes['class'], $row_class);
      }
      // Add row attributes to the item.
      if ($horizontal) {
        $items[$row]['attributes'] = new Attribute($row_attributes);
      }
      else {
        $items[$col]['content'][$row]['attributes'] = new Attribute($row_attributes);
      }
    }

    // Create attributes for columns.
    if ($horizontal || (!$horizontal && empty($items[$col]['attributes']))) {
      $col_attributes = ['class' => []];
      // Add default views column classes.
      // Add custom column classes.
      $col_class = array_filter(explode(' ', $variables['view']->style_plugin->getCustomClass($result_index, 'col')));
      if (!empty($col_class)) {
        $col_attributes['class'] = array_merge($col_attributes['class'], $col_class);
      }
      // Add automatic width for columns.
      if ($options['automatic_width']) {
        $col_attributes['style'] = 'width: ' . (100 / $options['columns']) . '%;';
      }
      // Add column attributes to the item.
      if ($horizontal) {
        $items[$row]['content'][$col]['attributes'] = new Attribute($col_attributes);
      }
      else {
        $items[$col]['attributes'] = new Attribute($col_attributes);
      }
    }

    // Increase, decrease or reset appropriate integers.
    if ($horizontal) {
      if ($col == 0 && $col != ($options['columns'] - 1)) {
        $col++;
      }
      elseif ($col >= ($options['columns'] - 1)) {
        $col = 0;
        $row++;
      }
      else {
        $col++;
      }
    }
    else {
      $row++;
      if (!$remainders && $row == $num_rows) {
        $row = 0;
        $col++;
      }
      elseif ($remainders && $row == $num_rows + 1) {
        $row = 0;
        $col++;
        $remainders--;
      }
    }
  }

  // Add items to the variables array.
  $variables['items'] = $items;
}
