<?php
/**
 * @file
 *   Theme definition.
 */


/**
 * Template preprocess views view matrix
 * @param &$vars an associative array with the following keys:
 *   view: the view object
 *   options: the options
 *   rows: the rendered rows
 *   title: the title of the view
 */
function template_preprocess_views_view_matrix(&$vars) {

  drupal_add_css(drupal_get_path('module', 'views_matrix') . '/views_matrix.css');

  // @see template_preprocess_views_view_table
  $result = $vars['result'] = $vars['rows'];
  $vars['rows'] = array();
  $vars['header'] = array('');
  $vars['row_classes'] = array();
  // @todo Figure out why views is replacing attributes with a string.
  $vars['matrix_attributes'] = array(
    'class' => array('views-matrix'),
  );

  $view = &$vars['view'];
  $options = $view->style_plugin->options;
  $handler = $view->style_plugin;
  $fields = &$view->field;

  // Add sticky table header if enabled.
  if (!empty($options['sticky']) && $options['sticky']) {
    drupal_add_js('misc/tableheader.js');
    $vars['matrix_attributes']['class'][] = 'sticky-enabled';
  }

  $col_header_classes = array('views-matrix-col-header');
  $sortable_headers = !empty($options['sortable_headers']);
  $sort_x = NULL;
  if ($sortable_headers) {
    $col_header_classes[] = 'views-field';
    $sort_x = isset($_GET['order']) ? $_GET['order'] : NULL;
    $query = tablesort_get_query_parameters();
    if (isset($view->exposed_raw_input)) {
      $query += $view->exposed_raw_input;
    }
  }

  $first_column_header = '';
  $sort_by_row_header = FALSE;
  if (isset($options['xconfig']['first_column'])) {
    $first_column_header = $options['xconfig']['first_column'];
    $sort_by_row_header = $sort_x === $first_column_header;
  }

  $errors = $handler->canRender();
  if (!empty($errors)) {
    // Validation of row and column headers during runtime so it doesn't explode.
    $message = theme('item_list', array('items' => $errors));
    drupal_set_message(t('The view could not be rendered due to the following errors: !error', array('!error' => $message)), 'error');
    return;
  }

  $xfield_id = $options['xconfig']['field'];
  $yfield_id = $options['yconfig']['field'];
  $xfield = &$fields[$xfield_id];
  $yfield = &$fields[$yfield_id];

  $columns = array();
  $rows = array();
  $row_header = array();

  // Render
  $renders = $handler->render_fields($result);

  // Storage for where things are.
  // Lookup of serialized values to the header and row header that shows them.
  $value_index_lookup = array();
  // Lookup of coordinates (the values in $value_index_lookup) for result
  // indexes, of the form $index => array(x => $index, y => $index), where the
  // indexes are values in $value_index_lookup.
  $coordinates = array();

  // Populate both the header and row header with unique values from the
  // respective fields. Both these arrays will be keyed by result index, which
  // will therefore not necessarily be sequential. This means we can later use
  // them as co-ordinates.
  foreach ($result as $index => $item) {
    // Get the values from our x and y fields.
    // Note that because of FieldAPI these may be arrays.
    $xvalue = is_object($item) ? $xfield->get_value($item) : '';
    $yvalue = is_object($item) ? $yfield->get_value($item) : '';

    if (!in_array($xvalue, $columns)) {
      // Add to header array, an array of $index => value.
      $columns[$index] = $xvalue;
      $value_index_lookup['x'][serialize($xvalue)] = $index;
      $columns_renders[$index] = $renders[$index][$xfield_id];
    }
    // Whether we've placed this item in the header or not, we can get its
    // coordinate.
    $coordinates[$index]['x'] = $value_index_lookup['x'][serialize($xvalue)];

    if (!in_array($yvalue, $rows)) {
      // Add to row header array.
      $row_header[$index] = array(
        'data' => $renders[$index][$yfield_id],
        'attributes' => array(
          'class' => array_merge(array('views-matrix-row-header'), $handler->headerClasses('y', $index)),
          'scope' => 'row',
        ),
      );
      $rows[$index] = $yvalue;
      $value_index_lookup['y'][serialize($yvalue)] = $index;
      $rows_renders[$index] = $renders[$index][$yfield_id];
    }
    $coordinates[$index]['y'] = $value_index_lookup['y'][serialize($yvalue)];
  }

  // Sorting.
  // This may not always make sense with FieldAPI fields, eg image fields.
  $sort_columns_asc = $options['xconfig']['sort'] == 'asc';
  $sort_rows_asc = $options['yconfig']['sort'] == 'asc';
  if (empty($options['xconfig']['sort_field'])) {
    if ($sort_columns_asc) {
      asort($columns_renders);
    }
    else {
      arsort($columns_renders);
    }
  }
  elseif (!empty($fields[$options['xconfig']['sort_field']])) {
    $sort_field = $fields[$options['xconfig']['sort_field']];
    $handler->sort_asc = $sort_columns_asc;
    $handler->sort_weights = array();
    foreach ($renders as $index => $row) {
      if (empty($handler->sort_weights[$row[$xfield_id]])) {
        $handler->sort_weights[$row[$xfield_id]] = $sort_field->get_value($result[$index]);
      }
    }
    uasort($columns_renders, array($handler, 'compareRows'));
  }

  // If we have a click sort set, sort the rows accordingly. (The behavior when
  // click-sorting on the first column is the same as a normal alphabetic sort
  // of the row headers, though, so we include a special case for that.)
  if ($sort_x && !$sort_by_row_header) {
    // Choose the first non-excluded field as the sort field. If there is none,
    // for some reason, fall back to the last field.
    $sort_field = NULL;
    foreach ($fields as $sort_field) {
      if (!$sort_field->options['exclude']) {
        break;
      }
    }
    $handler->sort_asc = isset($_GET['sort']) ? $_GET['sort'] === 'asc' : TRUE;
    $handler->sort_weights = array();
    foreach ($renders as $index => $row) {
      if ($row[$xfield_id] == $sort_x) {
        $handler->sort_weights[$row[$yfield_id]] = $sort_field->get_value($result[$index]);
      }
    }
    uasort($rows_renders, array($handler, 'compareRows'));
  }
  elseif ($sort_by_row_header || empty($options['yconfig']['sort_field'])) {
    if ($sort_by_row_header) {
      $sort_rows_asc = isset($_GET['sort']) ? $_GET['sort'] === 'asc' : TRUE;
    }
    if ($sort_rows_asc) {
      asort($rows_renders);
    }
    else {
      arsort($rows_renders);
    }
  }
  elseif (!empty($fields[$options['yconfig']['sort_field']])) {
    $sort_field = $fields[$options['yconfig']['sort_field']];
    $handler->sort_asc = $sort_rows_asc;
    $handler->sort_weights = array();
    foreach ($renders as $index => $row) {
      if (empty($handler->sort_weights[$row[$yfield_id]])) {
        $handler->sort_weights[$row[$yfield_id]] = $sort_field->get_value($result[$index]);
      }
    }
    uasort($rows_renders, array($handler, 'compareRows'));
  }

  // Create the header and rows arrays for theme_table(), populating the header
  // and row header cells, and padding out the table with empty cells.
  $left_fields = array();
  $right_fields = array();
  $vars['header'] = array();
  if ($first_column_header && $sortable_headers) {
    $sort = isset($options['header_init_sort']) ? $options['header_init_sort'] : 'asc';
    $title = t('sort by @s', array('@s' => $first_column_header));
    $query['order'] = $first_column_header;
    $first_column_header = check_plain($first_column_header);
    if ($sort_by_row_header) {
      $sort = $sort_rows_asc ? 'desc' : 'asc';
      $first_column_header .= theme('tablesort_indicator', array('style' => $sort));
    }

    $query['sort'] = $sort;
    $link_options = array(
      'html' => TRUE,
      'attributes' => array('title' => $title),
      'query' => $query,
    );
    $first_column_header = l($first_column_header, $_GET['q'], $link_options);
  }
  $vars['header'][] = array(
    'data' => $first_column_header,
    'attributes' => array(
      'class' => array(
        'views-matrix-col-header',
        'views-matrix-col-first',
      ),
    ),
  );

  // Place headers for fields left of the matrix.
  foreach ($fields as $field_name => $field_handler) {
    if (isset($options['field_positions'][$field_name])
        && $options['field_positions'][$field_name] === 'left'
        && empty($field_handler->options['exclude'])) {
      $vars['header'][] = array(
        'data' => $field_handler->label(),
        'attributes' => array(
          'class' => array_merge($col_header_classes, $handler->headerClasses('x')),
          'scope' => 'col',
        ),
      );
      $left_fields['_' . $field_name] = $field_name;
    }
  }

  // Place headers for fields displayed inside the matrix. Use the rendered
  // column array, as it is sorted.
  foreach ($columns_renders as $xindex => $col_render) {
    // If we want sortable headers, replace the rendered column header with a
    // sort link.
    if ($sortable_headers) {
      $sort = isset($options['header_init_sort']) ? $options['header_init_sort'] : 'asc';
      $title = t('sort by @s', array('@s' => $col_render));
      $query['order'] = $col_render;
      if ($sort_x === $col_render) {
        $sort = $handler->sort_asc ? 'desc' : 'asc';
        $col_render .= theme('tablesort_indicator', array('style' => $sort));
      }

      $query['sort'] = $sort;
      $link_options = array(
        'html' => TRUE,
        'attributes' => array('title' => $title),
        'query' => $query,
      );
      $col_render = l($col_render, $_GET['q'], $link_options);
    }
    // Place the rendered field in the table header.
    $vars['header'][] = array(
      'data' => $col_render,
      'attributes' => array(
        'class' => array_merge($col_header_classes, $handler->headerClasses('x', $xindex)),
        'scope' => 'col',
      ),
    );
  }

  // Place headers for fields right of the matrix.
  foreach ($fields as $field_name => $field_handler) {
    if (isset($options['field_positions'][$field_name])
      && $options['field_positions'][$field_name] === 'right'
      && empty($field_handler->options['exclude'])) {
      $vars['header'][] = array(
        'data' => $field_handler->label(),
        'attributes' => array(
          'class' => array_merge($col_header_classes, $handler->headerClasses('x')),
          'scope' => 'col',
        ),
      );
      $right_fields['_' . $field_name] = $field_name;
    }
  }

  // Fill up the table with the first column of each row from the row header,
  // and the rest of each row with blanks, using the index values from both the
  // header arrays so we can use our coordinates later.
  $xparity = $yparity = 0;
  $not_in_matrix = array_flip($left_fields + $right_fields);
  $column_indices = array_keys($left_fields + $columns_renders + $right_fields);
  foreach (array_keys($rows_renders) as $yindex) {
    // Set the row header.
    $vars['rows'][$yindex]['header'] = $row_header[$yindex];
    // Add row class for each row.
    $vars['row_classes'][$yindex][] = ($xparity % 2 == 0) ? 'even' : 'odd';
    $vars['row_classes'][$yindex][] = isset($options['row class']) ? views_css_safe($options['row class']) : '';

    $yparity = 0;
    // Fill in blank cells.
    foreach ($column_indices as $xindex) {
      $classes = array('views-matrix-cell', 'views-matrix-empty');
      $classes[] = (($xparity + $yparity) % 2 === 0) ? 'even' : 'odd';
      $vars['rows'][$yindex][$xindex] = array(
        'data' => '',
        'attributes' => array('class' => $classes),
      );
      $yparity++;
    }
    $xparity++;
  }

  $empty_columns = $options['hide_empty_columns'] ? array_fill_keys(array_keys($columns_renders), TRUE) : array();
  $empty_rows = $options['hide_empty_rows'] ? array_fill_keys(array_keys($vars['rows']), TRUE) : array();
  $limit_results = $options['one_result_per_cell'];

  foreach ($result as $index => $item) {
    // Get the cell's coordinates from the array.
    $x_coordinate = $coordinates[$index]['x'];
    $y_coordinate = $coordinates[$index]['y'];

    // If the "Limit to one result per table cell" option is active and there is
    // already data in this table cell, skip this result row.
    if ($limit_results && !empty($vars['rows'][$y_coordinate][$x_coordinate]['data'])) {
      continue;
    }

    // Go through each view row and repack the item after markup.
    $rendered_item = '';

    foreach (element_children($fields) as $field_name) {
      // Create the markup for the field.
      $field_handler = &$fields[$field_name];

      if ($field_handler->options['exclude']) {
        // Don't render the field.
        continue;
      }

      // Obey the "Hide if empty" setting.
      if ($field_handler->options['hide_empty'] && (string) $renders[$index][$field_name] === '') {
        continue;
      }

      // Field CSS id.
      $field_css = views_clean_css_identifier($field_name);

      // Field wrapper
      $wrapper_type = $field_handler->element_wrapper_type();
      $field_output = '<' . $wrapper_type;
      if ($field_handler->options['element_default_classes']) {
        $wrapper_classes = 'views-field views-field-' . $field_css;
      }
      else {
        $wrapper_classes = '';
      }
      $wrapper_classes .= $field_handler->element_wrapper_classes($index);
      $field_output .= (!empty($wrapper_classes)) ? ' class="' . $wrapper_classes . '">' : '>';

      if ($label = $field_handler->label()) {
        // Field label
        $label_html = $label;
        if ($field_handler->options['element_label_colon']) {
          $label_html .= ': ';
        }

        $element_label_type = $field_handler->element_label_type();
        if ($element_label_type) {
          $class = '';
          if ($field_handler->options['element_default_classes']) {
            $class = 'views-label views-label-' . $field_css;
          }

          $element_label_class = $field_handler->element_label_classes($index);
          if ($element_label_class) {
            if ($class) {
              $class .= ' ';
            }

            $class .= $element_label_class;
          }

          $pre = '<' . $element_label_type;
          if ($class) {
            $pre .= ' class="' . $class . '"';
          }
          $pre .= '>';

          $field_output .= $pre . $label_html . '</' . $element_label_type . '>';
        }
      }

      // Field
      $field_type = $field_handler->element_type();
      $field_output .= '<' . $field_type;
      $field_classes = $field_handler->element_classes($index);
      $field_output .= (!empty($field_classes)) ? ' class="' . $field_classes . '">' : '>';
      $field_output .= $renders[$index][$field_name];
      $field_output .= '</' . $field_type . '>';

      $field_output .= '</' . $wrapper_type . '>';

      // Render the field inside or outside the matrix.
      if (empty($not_in_matrix[$field_name])) {
        $rendered_item .= $field_output;
      }
      else {
        $vars['rows'][$coordinates[$index]['y']]['_' . $field_name]['data'] .= $field_output;
      }
    }

    // The row number is y, the column number is x.
    $vars['rows'][$y_coordinate][$x_coordinate]['data'] .= $rendered_item;

    if ($rendered_item) {
      unset($empty_columns[$x_coordinate]);
      unset($empty_rows[$y_coordinate]);
    }

    // Remove class views-matrix-empty
    $vars['rows'][$y_coordinate][$x_coordinate]['attributes']['class'] = array_diff($vars['rows'][$y_coordinate][$x_coordinate]['attributes']['class'], array('views-matrix-empty'));
  }

  if ($empty_rows) {
    $vars['rows'] = array_diff_key($vars['rows'], $empty_rows);
  }
  if ($empty_columns) {
    // $header_index_lookup contains the x coordinates mapped to their index in
    // the "header" variable minus 1, since $column_renders is keyed by x
    // coordinate and in the order the cells were added to the header. The
    // "minus 1" part comes from the first, empty header cell.
    $header_index_lookup = array_flip(array_keys($columns_renders));
    foreach (array_keys($empty_columns) as $column) {
      unset($vars['header'][$header_index_lookup[$column] + 1]);
    }
    foreach ($vars['rows'] as $y_coordinate => $row) {
      $vars['rows'][$y_coordinate] = array_diff_key($vars['rows'][$y_coordinate], $empty_columns);
    }
  }

  // Apply the "Add row numbers" option, if enabled. This has to happen last,
  // because otherwise removing empty rows might mess up the numbering.
  if ($options['yconfig']['row_numbers']) {
    $row_number = 0;
    $row_header_format = $options['yconfig']['row_number_format'];
    foreach ($vars['rows'] as &$row) {
      $header = &$row['header'];
      if (isset($header['data'])) {
        $header = &$header['data'];
      }
      if ($header) {
        $tokens = array(
          '[counter]' => ++$row_number,
          '[label]' => $header,
        );
        $header = strtr($row_header_format, $tokens);
      }
      unset($header);
    }
    unset($row);
  }
}
