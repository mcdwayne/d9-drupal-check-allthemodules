<?php

/**
 * @file
 * Contains Views hooks.
 */

/**
 * Implements hook_views_data_alter().
 */
function spectra_flat_views_data_alter(array &$data) {
  $data['spectra_flat_statement']['spectra_flat_statement']['relationship'] = [
    'title' => t('Child Statements'),
    'help' => t('Child Statements'),
    'base' => 'spectra_flat_statement',
    'base field' => 'parent_flat_statement',
    'relationship field' => 'flat_statement_id',
    'id' => 'standard',
    'label' => t('Child Statements'),
  ];

  $data['spectra_flat_statement']['spectra_flat_statement_data_viewer'] = [
    'title' => t('Render stored data'),
    'help' => t('Renders data in Flat Statement in a specified format.'),
    'field' => [
      'id' => 'spectra_flat_statement_data_viewer',
    ],
  ];

}