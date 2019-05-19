<?php

/**
 * @file
 * Theme for Views table search views.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Template_preprocess_views_view_tablesearch.
 */
function template_preprocess_views_view_tablesearch(&$variables) {
  $view = $variables['view'];

  // We need the raw data for this grouping, which is passed in
  // as $variables['rows'].
  // However, the template also needs to use for the rendered fields.  We
  // therefore swap the raw data out to a new
  // variable and reset $variables['rows']
  // so that it can get rebuilt.
  // Store rows so that they may be used by further preprocess functions.
  $result = $variables['result'] = $variables['rows'];
  $variables['rows'] = [];
  $variables['header'] = [];

  $options = $view->style_plugin->options;

  $handler = $view->style_plugin;
  $fields = &$view->field;
  $columns = $handler->sanitizeColumns($options['columns'], $fields);

  $active = !empty($handler->active) ? $handler->active : '';
  $order = !empty($handler->order) ? $handler->order : 'asc';

  // A boolean variable which stores whether the table has a responsive class.
  $responsive = FALSE;

  // For the actual site we want to not render full URLs, because this would
  // make pagers cacheable per URL, which is problematic in blocks, for example.
  // For the actual live preview though the javascript relies on properly
  // working URLs.
  $route_name = !empty($view->live_preview) ? '<current>' : '<none>';

  $query = tablesort_get_query_parameters();
  if (isset($view->exposed_raw_input)) {
    $query += $view->exposed_raw_input;
  }

  $variables['#attached']['library'][] = 'views_tablesearch/views_tablesearch';
  $variables['#attached']['drupalSettings']['tablesearch']['characters'] = $options['characters'];
  $variables['#attached']['drupalSettings']['tablesearch']['highlightColor'] = $options['highlight_color'];
  $variables['#attached']['drupalSettings']['tablesearch']['highlightClass'] = $options['highlight_class'];
  $variables['#attached']['drupalSettings']['tablesearch']['highlightElement'] = $options['highlight_element'];

  $variables['#attached']['drupalSettings']['tablesearch']['caseSensitiveSearch'] = $options['casesensitivesearch'];

  $variables['case_sensitive_expose'] = $options['casesensitiveexpose'];
  $variables['case_sensitive_search'] = $options['casesensitivesearch'];

  $variables['tablesearch_label'] = $options['searchlabel'] != '' ? t($options['searchlabel']) : '';
  $variables['case_sensitive_label'] = $options['casesensitivelabel'] != '' ? t($options['casesensitivelabel']) : '';

  // $variables['view']->element['#attached']['library'][].
  // A boolean to store whether the table's header has any labels.
  $has_header_labels = FALSE;
  $searchable = [];
  foreach ($columns as $field => $column) {
    // Create a second variable so we can easily find what fields we have and
    // what the CSS classes should be.
    $variables['fields'][$field] = Html::cleanCssIdentifier($field);
    if ($active == $field) {
      $variables['fields'][$field] .= ' is-active';
    }

    $searchable[$field] = $options[$field]['tablesearchable'];

    // Render the header labels.
    if ($field == $column && empty($fields[$field]->options['exclude'])) {
      $label = !empty($fields[$field]) ? $fields[$field]->label() : '';
      if (empty($options['info'][$field]['sortable']) || !$fields[$field]->clickSortable()) {
        $variables['header'][$field]['content'] = $label;
      }
      else {
        $initial = !empty($options['info'][$field]['default_sort_order']) ? $options['info'][$field]['default_sort_order'] : 'asc';

        if ($active == $field) {
          $initial = ($order == 'asc') ? 'desc' : 'asc';
        }

        $title = t('sort by @s', ['@s' => $label]);
        if ($active == $field) {
          $variables['header'][$field]['sort_indicator'] =
          [
            '#theme' => 'tablesort_indicator',
            '#style' => $initial,
          ];
        }

        $query['order'] = $field;
        $query['sort'] = $initial;
        $link_options = [
          'query' => $query,
        ];
        $url = new Url($route_name, [], $link_options);
        $variables['header'][$field]['url'] = $url->toString();
        $variables['header'][$field]['content'] = $label;
        $variables['header'][$field]['title'] = $title;
      }

      $variables['header'][$field]['default_classes'] = $fields[$field]->options['element_default_classes'];
      // Set up the header label class.
      $variables['header'][$field]['attributes'] = [];
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

      // Reference to the column in the loop to make the code easier to read.
      $column_reference = &$variables['rows'][$num]['columns'][$column];

      $column_reference['default_classes'] = $fields[$field]->options['element_default_classes'];

      // Set the field key to the column so it can be used for adding classes
      // in a template.
      $column_reference['fields'][] = $variables['fields'][$field];

      // Add field classes.
      if (!isset($column_reference['attributes'])) {
        $column_reference['attributes'] = [];
      }

      if ($classes = $fields[$field]->elementClasses($num)) {
        $column_reference['attributes']['class'][] = $classes;
      }

      // Add responsive header classes.
      if (!empty($options['info'][$field]['responsive'])) {
        $column_reference['attributes']['class'][] = $options['info'][$field]['responsive'];
      }

      // Improves accessibility of complex tables.
      if (isset($variables['header'][$field]['attributes']['id'])) {
        $column_reference['attributes']['headers'] = [$variables['header'][$field]['attributes']['id']];
      }

      if (!empty($fields[$field])) {
        $field_output = $handler->getField($num, $field);
        $column_reference['wrapper_element'] = $fields[$field]->elementType(TRUE, TRUE);
        if (!isset($column_reference['content'])) {
          $column_reference['content'] = [];
        }

        // Only bother with separators and stuff if the field shows up.
        // Place the field into the column, along with an optional separator.
        if (trim($field_output) != '') {
          if (!empty($column_reference['content']) && !empty($options['info'][$column]['separator'])) {
            $column_reference['content'][] = [
              'separator' => ['#markup' => $options['info'][$column]['separator']],
              'field_output' => ['#markup' => $field_output],
            ];
          }
          else {
            $column_reference['content'][] = [
              'field_output' => ['#markup' => $field_output],
            ];
          }
        }
      }
      $column_reference['attributes'] = new Attribute($column_reference['attributes']);
    }

    // Remove columns if the "empty_column" option is checked and the
    // field is empty.
    if (!empty($options['info'][$field]['empty_column'])) {
      $empty = TRUE;
      foreach ($variables['rows'] as $columns) {
        $empty &= empty($columns['columns'][$column]['content']);
      }
      if ($empty) {
        foreach ($variables['rows'] as &$column_items) {
          unset($column_items['columns'][$column]);
        }
        unset($variables['header'][$column]);
      }
    }
  }

  // Hide table header if all labels are empty.
  if (!$has_header_labels) {
    $variables['header'] = [];
  }

  foreach ($variables['rows'] as $num => $row) {
    $variables['rows'][$num]['attributes'] = [];
    if ($row_class = $handler->getRowClass($num)) {
      $variables['rows'][$num]['attributes']['class'][] = $row_class;
    }
    $variables['rows'][$num]['attributes'] = new Attribute($variables['rows'][$num]['attributes']);
  }

  if (empty($variables['rows']) && !empty($options['empty_table'])) {
    $build = $view->display_handler->renderArea('empty');
    $variables['rows'][0]['columns'][0]['content'][0]['field_output'] = $build;
    $variables['rows'][0]['attributes'] = new Attribute(['class' => 'odd']);
    // Calculate the amounts of rows with output.
    $variables['rows'][0]['columns'][0]['attributes'] = new Attribute(
      [
        'colspan' => count($variables['header']),
        'class' => 'views-empty',
      ]
    );
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
  elseif (!empty($variables['title'])) {
    $variables['caption'] = ['#markup' => $variables['title']];
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

  $size = count($variables['rows']);

  foreach ($variables['header'] as $field => $column) {
    for ($i = 0; $i < $size; $i++) {
      $variables['rows'][$i]['columns'][$field]['tablesearchable'] = $searchable[$field];
    }
  }
}



/**
 * Implements hook_theme_suggestions_HOOK().
 */
function tablesearch_theme_suggestions_views_ui_view_preview_section(array $variables) {
  return ['views_ui_view_preview_section__' . $variables['section']];
}
