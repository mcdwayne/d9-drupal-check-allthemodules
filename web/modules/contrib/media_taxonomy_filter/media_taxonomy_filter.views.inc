<?php

/**
 * @file
 * Provides views data for media_taxonomy_filter module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_views_data_alter().
 */
function media_taxonomy_filter_views_data_alter(&$data) {
  $data['media_field_data']['term_media_tid_depth'] = [
    'help' => t('Display medias if it has the selected taxonomy terms, or children of the selected terms.'),
    'real field' => 'mid',
    'argument' => [
      'title' => t('Media has taxonomy term ID (with depth)'),
      'id' => 'taxonomy_index_tid_media_depth',
      'accept depth modifier' => TRUE,
    ],
    'filter' => [
      'title' => t('Media has taxonomy term ID (with depth)'),
      'id' => 'taxonomy_index_tid_media_depth',
    ]
  ];
}

/**
 * Implements hook_views_plugins_argument_validator_alter().
 *
 * Extend the generic entity argument validator.
 *
 * @see \Drupal\views\Plugin\views\argument_validator\Entity
 */
function media_taxonomy_filter_views_plugins_argument_validator_alter(array &$plugins) {
  $plugins['entity:taxonomy_term']['title'] = t('Taxonomy term ID');
  $plugins['entity:taxonomy_term']['class'] = 'Drupal\taxonomy\Plugin\views\argument_validator\Term';
  $plugins['entity:taxonomy_term']['provider'] = 'taxonomy';
}