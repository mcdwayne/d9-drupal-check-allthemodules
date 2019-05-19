<?php

/**
 * @file
 * Provides views data for views_taxonomy_parent_id_from_term module.
 */

/**
 * Implements hook_views_data_alter().
 */
function views_taxonomy_parent_id_from_term_views_data_alter(&$data) {
  $data['taxonomy_term__parent']['term_parent'] = [
    'title' => t('Parent ID From Term'),
    'help' => t('The parent id of the term. This can produce duplicate entries if you are using a vocabulary that allows multiple parents.'),
    'real field' => 'parent_target_id',
    'relationship' => [
      'base' => 'taxonomy_term_field_data',
      'field' => 'parent_target_id',
      'label' => t('Parent ID From Term'),
      'id' => 'standard',
    ],
    'filter' => [
      'help' => t('Filter the results of "Taxonomy: Term" by the parent id.'),
      'id' => 'numeric',
    ],
    'argument' => [
      'help' => t('The parent id of the term.'),
      'id' => 'taxonomy',
    ],
  ];
}
