<?php
/**
 * @file
 * Eat drush commands.
 */

use Drupal\eat\Eat;


/**
 * Implments hook_drush_command().
 *
 * drush eatas 99999 dave 1
 *
 * @return array
 */
function eat_drush_command() {
  $items = [];
  $items['eat-add-single'] = [
    'description' => 'Adds a single eat entry',
    'arguments' => [
      'etid' => 'The entity id',
      'tid' => 'Term ID',
      'vid' => 'Vocabulary name'
    ],
    'aliases' => ['eatas']
  ];

  return $items;
}

/**
 * Add Single Entity.
 *
 * @param string $entity_id
 * @param string $title
 * @param string $vid
 */
function drush_eat_add_single($entity_id = '', $title = '', $vid = '') {
  Eat::createEatEntry($title, $entity_id, $vid);
}