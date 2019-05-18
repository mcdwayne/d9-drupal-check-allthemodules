<?php

/**
 * @file
 * Contains drush integration.
 */

use Drupal\permanent_entities\Entity\PermanentEntity;
use Drupal\permanent_entities\Entity\PermanentEntityType;

/**
 * Implements hook_drush_command().
 */
function permanent_entities_drush_command() {

  $commands['permanent-entity-create'] = [
    'description' => 'Creates a permenent entity.',
    'aliases' => ['pec'],
    'arguments' => [
      'type' => 'The permanent entity type',
      'id' => 'The permanent entity id',
      'label' => 'The permanent entity label.',
    ],
      'examples' => [
        'drush pec planet jupiter Jupiter' => 'Creates a planet with id jupiter and label Jupiter',
     ],
  ];

  return $commands;
}

/**
 * Creates a Permanent entity.
 */
function drush_permanent_entities_permanent_entity_create($type = '', $id = '', $label = '') {
  if (empty($type)) {
    return drush_user_abort('The permanent entity type is required');
  }
  if (empty($id)) {
    return drush_user_abort('The permanent entity id is required');
  }
  if (empty($label)) {
    return drush_user_abort('The permanent entity label is required');
  }

  if (!PermanentEntityType::load($type)) {
    return drush_set_error(dt('The permanent entity type "@type" does not exist.', ['@type' => $type]));
  }
  PermanentEntity::create([
    'label' => $label,
    'id' => $id,
    'type' => $type,
  ])->save();

  drush_print(dt('Permanent entity created'));
}
