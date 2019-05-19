<?php

/**
 * @file
 * Contains Views hooks.
 */

// TODO add noun/verb entries

/**
 * Implements hook_views_data_alter().
 */
function spectra_views_data_alter(array &$data) {
  // Statement reverse references
  $data['spectra_verb']['spectra_statement']['relationship'] = [
    'title' => t('Statement Referencing Verb as Action'),
    'help' => t('Statement Referencing Verb as Action'),
    'base' => 'spectra_statement',
    'base field' => 'action_id',
    'relationship field' => 'verb_id',
    'id' => 'standard',
    'label' => t('Statement Referencing Action'),
  ];
  $data['spectra_noun']['spectra_statement_actor']['relationship'] = [
    'title' => t('Statement Referencing Noun as Actor'),
    'help' => t('Statement Referencing Noun as Actor'),
    'base' => 'spectra_statement',
    'base field' => 'actor_id',
    'relationship field' => 'noun_id',
    'id' => 'standard',
    'label' => t('Statement Referencing Actor'),
  ];
  $data['spectra_noun']['spectra_statement_object']['relationship'] = [
    'title' => t('Statement Referencing Noun as Object'),
    'help' => t('Statement Referencing Noun as Object'),
    'base' => 'spectra_statement',
    'base field' => 'object_id',
    'relationship field' => 'noun_id',
    'id' => 'standard',
    'label' => t('Statement Referencing Object'),
  ];
  $data['spectra_noun']['spectra_statement_context']['relationship'] = [
    'title' => t('Statement Referencing Noun as Context'),
    'help' => t('Statement Referencing Noun as Context'),
    'base' => 'spectra_statement',
    'base field' => 'context_id',
    'relationship field' => 'noun_id',
    'id' => 'standard',
    'label' => t('Statement Referencing Context'),
  ];

  // Statement/data and statement/statement relationships
  $data['spectra_statement']['spectra_data']['relationship'] = [
    'title' => t('Data Referencing Statement'),
    'help' => t('Data Referencing Statement'),
    'base' => 'spectra_data',
    'base field' => 'statement_id',
    'relationship field' => 'statement_id',
    'id' => 'standard',
    'label' => t('Data Referencing Statement'),
  ];

  $data['spectra_statement']['spectra_statement']['relationship'] = [
    'title' => t('Child Statements'),
    'help' => t('Child Statements'),
    'base' => 'spectra_statement',
    'base field' => 'parent_statement',
    'relationship field' => 'statement_id',
    'id' => 'standard',
    'label' => t('Child Statements'),
  ];

  // Data Viewers
  $data['spectra_noun']['spectra_noun_viewer'] = [
    'title' => t('Render additional data'),
    'help' => t('Renders data in Spectra Verb in a specified format.'),
    'field' => [
      'id' => 'spectra_noun_viewer',
    ],
  ];
  $data['spectra_verb']['spectra_verb_viewer'] = [
    'title' => t('Render additional data'),
    'help' => t('Renders data in Spectra Verb in a specified format.'),
    'field' => [
      'id' => 'spectra_verb_viewer',
    ],
  ];
  $data['spectra_data']['spectra_data_viewer'] = [
    'title' => t('Render stored data'),
    'help' => t('Renders data in Spectra data in a specified format.'),
    'field' => [
      'id' => 'spectra_data_viewer',
    ],
  ];

}