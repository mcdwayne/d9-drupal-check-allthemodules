<?php

/**
 * @file
 * Table design test.
 */

/**
 * Two tables of dummy content with theme_table().
 */
function design_test_page_table() {
  // Simple Table
  $table_simple = design_test_dummy_table();
  $output['table_simple'] = array(
    '#theme' => 'table',
    '#prefix' => '<h2>' . t('Simple Table') . '</h2>',
    '#header' => $table_simple['header'],
    '#rows' => $table_simple['rows'],
    '#caption' => $table_simple['caption'],
   );

  // Tabledrag Table
  $table = design_test_dummy_table(array('rows' => 8, 'min' => 24, 'max' => 24, 'tabledrag' => TRUE, 'id' => 'table-tabledrag'));
  $output['table_tabledrag'] = array(
    '#theme' => 'table',
    '#prefix' => '<h2>' . t('Tabledrag Table') . '</h2>',
    '#header' => $table['header'],
    '#rows' => $table['rows'],
    '#attributes' => $table['attributes'],
    '#caption' => $table['caption'],
  );

  return $output;
}

/**
 * Helper function to return a table.
 *
 * @param $options
 *   (optional) An associative array containing:
 *   - rows: Number of rows to output in the table.
 *   - min: Minimum number of words to create when generating dummy text.
 *   - max: Maximum number of words to create when generating dummy text.
 *   - tabledrag: Boolean specifying whether to add tabledrag functionality.
 *   - id: ID attribute for <table>, required by tabledrag.
 */
function design_test_dummy_table($options = array()) {
  $options += array(
    'rows' => 5,
    'min' => 1,
    'max' => 3,
    'tabledrag' => FALSE,
    'id' => '',
  );
  $rows = $options['rows'];
  $min = $options['min'];
  $max = $options['max'];
  $tabledrag = $options['tabledrag'];
  $id = $options['id'];

  // Create table.
  $table = array();
  $table['caption'] = t('Table Caption');
  $table['header'] = array(t('Column 1'), t('Column 2'), t('Column 3'));
  $table['attributes'] = array('id' => $id);
  $table['rows'] = array();

  // Add tabledrag behavior.
  if ($tabledrag && $id) {
    $table['header'][] = t('Column 4');
    drupal_add_tabledrag($id, 'order', 'sibling', 'weight');
  }

  // Add rows.
  $i = 0;
  while ($i < $rows) {
    $i++;
    $words = rand($min, $max);
    $text = design_test_create_greeking($words);

    // Add tabledrag class
    if ($tabledrag) {
      $table['rows'][] = array(
        'data' => array(t('some text'), l(t('some link'), "#"), $text, array('data' => $i, 'class' => array('weight'))),
        'class' => array('draggable'),
      );
    }
    else {
      $table['rows'][] = array('data' => array(t('some text'), l(t('some link'), "#"), $text));
    }
  }

  return $table;
}
