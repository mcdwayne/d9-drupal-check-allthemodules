<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Prepares variables for views table templates.
 *
 *
 * Copied from views\theme\theme.inc.
 * Version history: Aligned with Views 7.x-3.6, not changed in 3.13

 * Default template: views-view-table.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */

/**
 * Display a view as a table style.
 * Copied from views\theme\theme.inc.
 * Version history: Aligned with Views 7.x-3.6, not changed in 3.13
 */
function template_preprocess_views_secondary_row_view_table(&$variables) {
  $view = $variables['view'];

  // We need the raw data for this grouping, which is passed in
  // as $variables['rows'].
  // However, the template also needs to use for the rendered fields.  We
  // therefore swap the raw data out to a new variable and reset $variables['rows']
  // so that it can get rebuilt.
  // Store rows so that they may be used by further preprocess functions.
  $result = $variables['result'] = $variables['rows'];
  $variables['rows'] = array();
  $variables['header'] = array();

  $options = $view->style_plugin->options;
  $handler = $view->style_plugin;

  $fields = &$view->field;
  $columns = $handler->sanitizeColumns($options['columns'], $fields);

  $active = !empty($handler->active) ? $handler->active : '';
  $order = !empty($handler->order) ? $handler->order : 'asc';

  // A boolean variable which stores whether the table has a responsive class.
  $responsive = FALSE;

  // Check if secondary rows are specified. If not, just call the default 'table' preprocessing.
  $has_secondary_row = FALSE;
  foreach ($columns as $field => $column) {
    if (!empty($options['info'][$field]['break2'])) {
      $has_secondary_row = TRUE;
    }
  }
  if (!$has_secondary_row) {
    // Fallback from views_secondary_row-template to views-template does not work.
    // template_preprocess_views_view_table($variables);
    // return;
  }
  $default_row_class = isset($options['default_row_class']) ? $options['default_row_class'] : TRUE;
  $special_row_class = isset($options['special_row_class']) ? $options['special_row_class'] : TRUE;

  $query = tablesort_get_query_parameters();
  if (isset($view->exposed_raw_input)) {
    $query += $view->exposed_raw_input;
  }

  // A boolean to store whether the table's header has any labels.
  $has_header_labels = FALSE;
  foreach ($columns as $field => $column) {
    // Create a second variable so we can easily find what fields we have and
    // what the CSS classes should be.
    $variables['fields'][$field] = Html::cleanCssIdentifier($field);
    if ($active == $field) {
      $variables['fields'][$field] .= ' is-active';
    }

    // Render the header labels.
    // Skip the header labels that are broken into the secondary row.
    if ($field == $column && empty($fields[$field]->options['exclude']) && empty($options['info'][$field]['break2'])) {

      $label = !empty($fields[$field]) ? $fields[$field]->label() : '';
      if (empty($options['info'][$field]['sortable']) || !$fields[$field]->clickSortable()) {
        $variables['header'][$field]['content'] = $label;
      }
      else {
        $initial = !empty($options['info'][$field]['default_sort_order']) ? $options['info'][$field]['default_sort_order'] : 'asc';

        if ($active == $field) {
          $initial = ($order == 'asc') ? 'desc' : 'asc';
        }

        $title = t('sort by @s', array('@s' => $label));
        if ($active == $field) {
          $variables['header'][$field]['sort_indicator'] = array(
            '#theme' => 'tablesort_indicator',
            '#style' => $initial,
          );
        }

        $query['order'] = $field;
        $query['sort'] = $initial;
        $link_options = array(
          'query' => $query,
        );
        // It is ok to specify no URL path here as we will always reload the
        // current page.
        $url = new Url('<none>', [], $link_options);
        $variables['header'][$field]['url'] = $url->toString();
        $variables['header'][$field]['content'] = $label;
        $variables['header'][$field]['title'] = $title;
      }

      $variables['header'][$field]['default_classes'] = $fields[$field]->options['element_default_classes'];
      // Set up the header label class.
      $variables['header'][$field]['attributes'] = array();
      $class = $fields[$field]->elementLabelClasses(0);
      if ($class) {
        $variables['header'][$field]['attributes']['class'][] = $class;
      }
      // Add responsive header classes.
      if (!empty($options['info'][$field]['responsive'])) {
        $variables['header'][$field]['attributes']['class'][] = $options['info'][$field]['responsive'];
        $responsive = TRUE;
      }
      // Add a CSS align class to each field if one was set.
      if (!empty($options['info'][$field]['align'])) {
        $variables['header'][$field]['attributes']['class'][] = Html::cleanCssIdentifier($options['info'][$field]['align']);
      }
      // Add a header label wrapper if one was selected.
      if ($variables['header'][$field]['content']) {
        $element_label_type = $fields[$field]->elementLabelType(TRUE, TRUE);
        if ($element_label_type) {
          $variables['header'][$field]['wrapper_element'] = $element_label_type;
        }
        // Improves accessibility of complex tables.
        $variables['header'][$field]['attributes']['id'] = Html::getUniqueId('view-' . $field . '-table-column');
      }
      // Check if header label is not empty.
      if (!empty($variables['header'][$field]['content'])) {
        $has_header_labels = TRUE;
      }

      $variables['header'][$field]['attributes'] = new Attribute($variables['header'][$field]['attributes']);
    }

    // Add a CSS align class to each field if one was set.
    if (!empty($options['info'][$field]['align'])) {
      $variables['fields'][$field] .= ' ' . Html::cleanCssIdentifier($options['info'][$field]['align']);
    }

    // Render each field into its appropriate column.
    foreach ($result as $num => $row) {

      // Skip building the attributes and content if the field is to be excluded
      // from the display.
      if (!empty($fields[$field]->options['exclude'])) {
        continue;
      }

      // Make all rows even rows, so that there's an empty odd/secondary row between them,
      // where the "secondary row" fields can be placed.
      $old_num = $num;
      $primary_row_num = $num * 2;
      $secondary_row_num = $primary_row_num + 1;
      if (!empty($options['info'][$field]['break2'])) {
        $num = $secondary_row_num;
//        $target_column = $options['info'][$field]['break2'];
        $column = $options['info'][$field]['break2'];
      }
      else {
        $num = $primary_row_num;
//        $target_column = $column;
      }

      // Reference to the column in the loop to make the code easier to read.
      $column_reference =& $variables['rows'][$num]['columns'][$column];

      $column_reference['default_classes'] = $fields[$field]->options['element_default_classes'];

      // Set the field key to the column so it can be used for adding classes
      // in a template.
      $column_reference['fields'][] = $variables['fields'][$field];

      // Add field classes.
      if (!isset($column_reference['attributes'])) {
        $column_reference['attributes'] = array();
      }

      if ($classes = $fields[$field]->elementClasses($num)) {
        $column_reference['attributes']['class'][] = $classes;
      }

      // Add colspan to secondary row.
      if ($num == $secondary_row_num && !empty($options['info'][$field]['break2']) && !empty($options['info'][$field]['colspan2'])) {
        $column_reference['attributes']['colspan'][] = $options['info'][$field]['colspan2'];
      }

      // Add responsive header classes.
      if (!empty($options['info'][$field]['responsive'])) {
        $column_reference['attributes']['class'][] = $options['info'][$field]['responsive'];
      }

      // Improves accessibility of complex tables.
      if (isset($variables['header'][$field]['attributes']['id'])) {
        $column_reference['attributes']['headers'] = array($variables['header'][$field]['attributes']['id']);
      }

      if (!empty($fields[$field])) {
        // Modification for secondary row. Use $old_num, or empty rows will appear.
        $field_output = $handler->getField($old_num, $field);
//        $field_output = $handler->getField($num, $field);
        $column_reference['wrapper_element'] = $fields[$field]->elementType(TRUE, TRUE);
        if (!isset($column_reference['content'])) {
          $column_reference['content'] = [];
        }

        // Only bother with separators and stuff if the field shows up.
        // Place the field into the column, along with an optional separator.
        if (trim($field_output) != '') {
          if ($num == $secondary_row_num && !empty($options['info'][$field]['break2'])) {
            if (!empty($options['info'][$column]['separator2'])) {
              $column_reference['content'][] = [
                'separator' => ['#markup' => $options['info'][$column]['separator2']],
                'field_output' => ['#markup' => $field_output]
              ];
            }
            else {
              $column_reference['content'][] = [
                'field_output' => ['#markup' => $field_output]
              ];
            }
          }
          else

          if (!empty($column_reference['content']) && !empty($options['info'][$column]['separator'])) {
            $column_reference['content'][] = [
              'separator' => ['#markup' => $options['info'][$column]['separator']],
              'field_output' => ['#markup' => $field_output]
            ];
          }
          else {
            $column_reference['content'][] = [
              'field_output' => ['#markup' => $field_output]
            ];
          }
        }
      }
      $column_reference['attributes'] = new Attribute($column_reference['attributes']);
    }

    // Remove columns if the option is hide empty column is checked and the
    // field is not empty.
    if (!empty($options['info'][$field]['empty_column'])) {
      $empty = TRUE;
      foreach ($variables['rows'] as $columns) {
        $empty &= empty($columns[$column]);
      }
      if ($empty) {
        foreach ($variables['rows'] as &$column_items) {
          unset($column_items[$column]);
          unset($variables['header'][$column]);
        }
      }
    }
  }

  // Reorder rows, since we appended the odd/secondary rows at the end of the list.
  ksort($variables['rows']);
  // Reorder columns, since order of fields in secondary rows may differ from target columns.
//  $column_reference =& $variables['rows'][$num]['columns'][$column];
  foreach ($variables['rows'] as $num => $row) {
    if ($num % 2 == 0) {
      continue;
    }
    $ordered = array();
     // @todo D8-port
    foreach ($columns as $field => $column) {
      if (isset($row['columns'][$column])) {
        $ordered['columns'][$column] = $row['columns'][$column];
      }
    }
    $variables['rows'][$num]['columns'] = $ordered['columns'];
  }

  // Hide table header if all labels are empty.
  if (!$has_header_labels) {
    $variables['header'] = array();
  }

  $count = 0;
  foreach ($variables['rows'] as $num => $row) {
    $variables['rows'][$num]['attributes'] = array();
    if ($row_class = $handler->getRowClass($num)) {
      $variables['rows'][$num]['attributes']['class'][] = $row_class;
    }
    $variables['rows'][$num]['attributes'] = new Attribute($variables['rows'][$num]['attributes']);
    /* // @todo test D8-port
        $variables['row_classes'][$num] = array();
        if ($special_row_class) {
          // Views secondary row: give both lines the same class.
          //$variables['row_classes'][$num][] = ($count++ % 2 == 0) ? 'odd' : 'even';
          $variables['row_classes'][$num][] = (($count++ / 2) % 2 == 0) ? 'odd' : 'even';
        }
        if ($row_class = $handler->get_row_class($num)) {
          $variables['row_classes'][$num][] = $row_class;
        }
      }

      if ($special_row_class) {
        $variables['row_classes'][0][] = 'views-row-first';
        $variables['row_classes'][count($variables['row_classes']) - 1][] = 'views-row-last';
    */
  }

  if (empty($variables['rows']) && !empty($options['empty_table'])) {
    $build = $view->display_handler->renderArea('empty');
    $variables['rows'][0]['columns'][0]['content'][0]['field_output'] = $build;
    $variables['rows'][0]['attributes'] = new Attribute(array('class' => 'odd'));
    // Calculate the amounts of rows with output.
    $variables['rows'][0]['columns'][0]['attributes'] = new Attribute(array(
      'colspan' => count($variables['header']),
      'class' => 'views-empty',
    ));
  }

  $variables['sticky'] = FALSE;
  if (!empty($options['sticky'])) {
    $variables['view']->element['#attached']['library'][] = 'core/drupal.tableheader';
    $variables['sticky'] = TRUE;
  }

  // Add the caption to the list if set.
  if (!empty($handler->options['caption'])) {
    $variables['caption'] = ['#markup' => $handler->options['caption']];
    $variables['caption_needed'] = TRUE;
  }
  else {
    $variables['caption'] = '';
    $variables['caption_needed'] = FALSE;
  }

  $variables['summary'] = $handler->options['summary'];
  $variables['description'] = $handler->options['description'];
  $variables['caption_needed'] |= !empty($variables['summary']) || !empty($variables['description']);

  $variables['responsive'] = FALSE;
  // If the table has headers and it should react responsively to columns hidden
  // with the classes represented by the constants RESPONSIVE_PRIORITY_MEDIUM
  // and RESPONSIVE_PRIORITY_LOW, add the tableresponsive behaviors.
  if (isset($variables['header']) && $responsive) {
    $variables['view']->element['#attached']['library'][] = 'core/drupal.tableresponsive';
    // Add 'responsive-enabled' class to the table to identify it for JS.
    // This is needed to target tables constructed by this function.
    $variables['responsive'] = TRUE;
  }
}

/**
 * Prepares variables for style plugin table templates.
 *
 * Copied from views_ui.theme.inc, and 3 columns inserted.
 * This shows the Views UI OptionsForm.
 * Version history: Aligned with Views 8.0.1.
 *
 * Default template: views-ui-style-plugin-table.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 */
function template_preprocess_views_secondary_row_style_plugin_table(&$variables) {
  // @todo: First, call the default views_ui function, then change.
  // template_preprocess_views_ui_style_plugin_table($variables);

  $form = $variables['form'];

  $header = array(
    t('Field'),
    t('Column'),
    t('Align'),
    t('Separator'),
    // Begin insert views_secondary_row
    array(
      'data' => t('Secondary row'),
      'align' => 'center',
    ),
    array(
      'data' => t('2nd row Separator'),
      'align' => 'center',
    ),
    array(
      'data' => t('2nd row Colspan'),
      'align' => 'center',
    ),
    // End insert views_secondary_row

    array(
      'data' => t('Sortable'),
      'align' => 'center',
    ),
    array(
      'data' => t('Default order'),
      'align' => 'center',
    ),
    array(
      'data' => t('Default sort'),
      'align' => 'center',
    ),
    array(
      'data' => t('Hide empty column'),
      'align' => 'center',
    ),
    array(
      'data' => t('Responsive'),
      'align' => 'center',
    ),
  );
  $rows = array();
  foreach (Element::children($form['columns']) as $id) {
    $row = array();
    $row[]['data'] = $form['info'][$id]['name'];
    $row[]['data'] = $form['columns'][$id];
    $row[]['data'] = $form['info'][$id]['align'];
    $row[]['data'] = $form['info'][$id]['separator'];
    // Begin insert views_secondary_row.
    $row[]['data'] = $form['info'][$id]['break2'];
    $row[]['data'] = $form['info'][$id]['separator2'];
    $row[]['data'] = $form['info'][$id]['colspan2'];
    // End insert views_secondary_row.

    if (!empty($form['info'][$id]['sortable'])) {
      $row[] = array(
        'data' => $form['info'][$id]['sortable'],
        'align' => 'center',
      );
      $row[] = array(
        'data' => $form['info'][$id]['default_sort_order'],
        'align' => 'center',
      );
      $row[] = array(
        'data' => $form['default'][$id],
        'align' => 'center',
      );
    }
    else {
      $row[] = '';
      $row[] = '';
      $row[] = '';
    }
    $row[] = array(
      'data' => $form['info'][$id]['empty_column'],
      'align' => 'center',
    );
    $row[] = array(
      'data' => $form['info'][$id]['responsive'],
      'align' => 'center',
    );
    $rows[] = $row;
  }

  // Add the special 'None' row.
  $rows[] = array(array('data' => t('None'), 'colspan' => 6), array('align' => 'center', 'data' => $form['default'][-1]), array('colspan' => 2));

  // Unset elements from the form array that are used to build the table so that
  // they are not rendered twice.
  unset($form['default']);
  unset($form['info']);
  unset($form['columns']);

  $variables['table'] = array(
    '#type' => 'table',
    '#theme' => 'table__views_ui_style_plugin_table',
    '#header' => $header,
    '#rows' => $rows,
  );
  $variables['form'] = $form;
}
