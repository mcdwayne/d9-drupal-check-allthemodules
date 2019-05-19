<?php

/**
 * @file
 * Defines theming processors for views_responsive_grid.
 */

use Drupal\Core\Template\Attribute;

/**
 * Process variables for views responsive grid style templates.
 *
 * Default template: views-view-responsive-grid.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: The view object.
 *   - rows: An array of row items. Each row is an array of content.
 *   - options: The view object style plugin options.
 */
function template_preprocess_views_view_responsive_grid(&$vars) {
  drupal_add_css(drupal_get_path('module', 'views_responsive_grid') . '/views_responsive_grid.css');
  
  $options = $vars['options'] = $vars['view']->style_plugin->options;
  $horizontal = ($options['alignment'] === 'horizontal');

  // View classes.
  $vars['attributes'] = new Attribute(array(
    'class' => array(
      'views-responsive-grid',
      $options['alignment'],
      'cols-' . $options['columns'],
      'clearfix',
    ),
  ));

  // Setup integers.
  $col = 0;
  $row = 0;
  $row_count = count($vars['rows']);
  $remainders = $row_count % $options['columns'];
  $num_rows = floor($row_count / $options['columns']);
  // Iterate over the view rows array.
  foreach ($vars['rows'] as $row_index => $item) {
    // Add the current views data row to the rows array.
    if ($horizontal) {
      $items[$row][$col] = $item;
    }
    else {
      $items[$col][$row] = $item;
    }

    // Create attributes for row.
    $row_attributes = array('class' => array());
    // Add default views row classes.
    if ($options['default_row_class']) {
      $row_attributes['class'][] = 'views-row';
      $row_attributes['class'][] = 'row-' . ($row + 1);
    }
    // Add special views row classes.
    if ($options['row_class_special']) {
      // First row.
      if ($row == 0) {
        $row_attributes['class'][] = 'first';
      }
      // Last row.
      if (($horizontal && $row == (!$remainders ? $num_rows - 1 : $num_rows)) || (!$horizontal && ((!$remainders && $row == $num_rows - 1) || ($remainders && $row == $num_rows)))) {
        $row_attributes['class'][] = 'last';
      }
      // Zebra striping.
      $row_attributes['class'][] = (($row + 1) % 2) ? 'odd' : 'even';
      // Clearfix
      if ($horizontal) {
        $row_attributes['class'][] = 'clearfix';
      }
    }
    // Add custom row class.
    $row_class = array_filter(explode(' ', $options['row_class']));
    if (!empty($row_class)) {
      $row_attributes['class'] = array_merge($row_attributes['class'], $row_class);
    }
    // Add row attributes to variables array.
    if ($horizontal) {
      $vars['row_attributes'][$row] = new Attribute($row_attributes);
    }
    else {
      $vars['row_attributes'][$col][$row] = new Attribute($row_attributes);
    }
  
    // Create attributes for columns.
    $col_attributes = array('class' => array());
    // Add default views column classes.
    if ($options['default_col_class']) {
      $col_attributes['class'][] = 'views-col';
      $col_attributes['class'][] = 'col-' . ($col + 1);
    }
    // Add special views row classes.
    if ($options['col_class_special']) {
      if ($col == 0) {
        $col_attributes['class'][] = 'first';
      }
      if ($col == $options['columns'] - 1 || ($row_count <= $num_rows && $col == $row_count)) {
        $col_attributes['class'][] = 'last';
      }
      // Zebra striping.
      $col_attributes['class'][] = (($col + 1) % 2) ? 'odd' : 'even';
      // Clearfix
      if (!$horizontal) {
        $col_attributes['class'][] = 'clearfix';
      }
    }
    // Add custom column class.
    $col_class = array_filter(explode(' ', $options['col_class']));
    if (!empty($col_class)) {
      $col_attributes['class'] = array_merge($col_attributes['class'], $col_class);
    }
    // Add width to columns.
    if ($options['columns'] > 1 && $options['automatic_width']) {
      $col_attributes['style'] = 'width: ' . (100 / $options['columns']) . '%;';
    }
    // Add column attributes to variables array.
    if ($horizontal) {
      $vars['col_attributes'][$row][$col] = new Attribute($col_attributes);
    }
    else {
      $vars['col_attributes'][$col] = new Attribute($col_attributes);
    }
    
    // Increase, decrease or reset the appropriate integers.
    if ($horizontal) {
      if ($col == 0) {
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
    $row_count--;
  }
  // Save the grid items to the variables array.
  $vars['items'] = $items;
}
