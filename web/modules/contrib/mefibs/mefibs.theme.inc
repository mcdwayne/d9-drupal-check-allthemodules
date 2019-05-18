<?php

/**
 * Theme the block listing in the mefibs config form in views ui.
 */
function theme_mefibs_views_ui_block_list(&$vars) {
  $form = $vars['form'];

  $header = array(
    '#',
    t('Name'),
    t('Machine name'),
    t('Filters'),
    t('Sorts'),
    '',
  );
  $rows = array();

  $count = 1;
  foreach (element_children($form) as $key) {
    if (!is_numeric($key)) {
      continue;
    }
    $row = array();
    $row[] = $key + 1;
    
    if ($form[$key]['name']['#type'] == 'markup') {
      $row[] = drupal_render($form[$key]['name']);
      $row[] = drupal_render($form[$key]['machine_name']);
      $row[] = drupal_render($form[$key]['filters']);
      $row[] = drupal_render($form[$key]['sorts']);
    }
    else {
      $row[] = array(
        'data' => drupal_render($form[$key]['name']) . drupal_render($form[$key]['machine_name']),
        'colspan' => 4,
      );
    }
    
    $row[] = drupal_render($form[$key]['actions']);
    $rows[] = $row;
    $count++;
  }

  // Add the special 'Default' row.
  $items = mefibs_get_expected_items_for_exposed_form_block($form['#view'], 'default');
  $rows[] = array(
    $count,
    array(
      'data' => t('Default block'),
      'colspan' => 2,
    ),
    count($items['filter']),
    count($items['sort']),
    '',
  );

  $output = theme('table', array(
    'rows' => $rows,
    'header' => $header,
  ));

  // Render the rest of the form and return.
  $output .= drupal_render_children($form);
  return $output;
}