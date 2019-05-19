<?php

use Drupal\x_reference\Entity\XReference;
use Drupal\x_reference\Entity\XReferencedEntity;
use Drupal\x_reference\XReferenceHandlerInterface;


/**
 * Implements hook_drush_command().
 */
function x_reference_drush_command() {
  $commands['x-reference-add'] = [
    'aliases' => ['x-ref-add'],
    'description' => 'Add new x-reference (and create x-referenced entities if create_if_not_exist options if not FALSE)',
    'options' => [
      'reference_type' => 'Reference type',
      'source_entity' => 'Source entity in format: "source:type:id"',
      'target_entity' => 'Target entity in format: "source:type:id"',
      'create_if_not_exist' => 'Create x-referenced entities if they are not exist (TRUE by default)',
    ],
  ];
  $commands['x-reference-delete'] = [
    'aliases' => ['x-ref-del'],
    'description' => 'Delete x-references',
    'options' => [
      'reference_ids' => 'Reference IDs, separated by comma',
    ],
  ];
  $commands['x-reference-get-by-source'] = [
    'aliases' => ['x-ref-get-by-source'],
    'description' => 'Gets all references for passed sources',
    'options' => [
      'reference_type' => 'Reference type',
      'entity_source' => 'Entity source',
      'entity_type' => 'Entity type',
      'entity_ids' => 'Entity ids, separated by comma',
    ],
    'callback' => 'drush_x_reference_get_by_entity',
    'callback arguments' => ['source'],
  ];
  $commands['x-reference-get-by-target'] = [
    'aliases' => ['x-ref-get-by-target'],
    'description' => 'Gets all references for passed targets',
    'options' => [
      'reference_type' => 'Reference type',
      'entity_source' => 'Entity source',
      'entity_type' => 'Entity type',
      'entity_ids' => 'Entity ids, separated by comma',
    ],
    'callback' => 'drush_x_reference_get_by_entity',
    'callback arguments' => ['target'],
  ];

  return $commands;
}

/**
 * Drush callback for 'x-reference-add' drush command.
 */
function drush_x_reference_add() {
  $options['reference_type'] = drush_get_option('reference_type');
  $options['source_entity'] = drush_get_option('source_entity');
  $options['target_entity'] = drush_get_option('target_entity');
  $options['create_if_not_exist'] = drush_get_option('create_if_not_exist', TRUE);

  $requiredOptions = [
    'reference_type',
    'source_entity',
    'target_entity',
  ];
  foreach ($requiredOptions as $requiredOptionName) {
    if (!$options[$requiredOptionName]) {
      return drush_set_error(dt('Missed required option: @option', ['@option' => $requiredOptionName]));
    }
  }
  /** @var XReferenceHandlerInterface $XReferenceHandler */
  $XReferenceHandler = \Drupal::service('x_reference_handler');

  if (!$XReferenceHandler->checkXReferenceType($options['reference_type'])) {
    return drush_set_error(dt('Unknown x-reference type: @type', ['@type' => $options['reference_type']]));
  }

  $XReferencedEntities = [];
  foreach (['source_entity', 'target_entity'] as $XReferencedEntityOptionName) {
    $entityParts = explode(':', $options[$XReferencedEntityOptionName]);
    if (count($entityParts) !== 3) {
      return drush_set_error(dt('Incorrect option passed: @option', ['@option' => $XReferencedEntityOptionName]));
    }
    $XReferencedEntity = $XReferenceHandler->createOrLoadXReferencedEntity(
      $entityParts[0],
      $entityParts[1],
      $entityParts[2],
      $options['create_if_not_exist']
    );
    if (!$options['create_if_not_exist'] && $XReferencedEntity->isNew()) {
      return drush_set_error(dt('Missing XReferencedEntity: @entity', ['@entity' => $options[$XReferencedEntityOptionName]]));
    }
    $XReferencedEntities[$XReferencedEntityOptionName] = $XReferencedEntity;
  }

  $XReference = $XReferenceHandler->createOrLoadXReference(
    $options['reference_type'],
    $XReferencedEntities['source_entity'],
    $XReferencedEntities['target_entity']
  );

  drush_print(dt('X-reference created: @id (@label)', ['@id' => $XReference->id(), '@label' => $XReference->label()]));
}

/**
 * Drush callback for 'x-reference-delete' drush command.
 */
function drush_x_reference_delete() {
  $options['reference_ids'] = drush_get_option('reference_ids', []);

  $requiredOptions = [
    'reference_ids',
  ];
  foreach ($requiredOptions as $requiredOptionName) {
    if (!$options[$requiredOptionName]) {
      return drush_set_error(dt('Missed required option: @option', ['@option' => $requiredOptionName]));
    }
  }
  $referenceIds = _x_reference_drush_handle_ids_param($options['reference_ids']);
  if (!$referenceIds) {
    return drush_set_error(dt('Incorrect option passed: @option', ['@option' => 'reference_ids']));
  }

  /** @var XReference[] $XReferences */
  $XReferences = XReference::loadMultiple($referenceIds);
  foreach ($XReferences as $XReference) {
    $XReference->delete();
    drush_print(dt('XReference @id (@label) deleted', ['@id' => $XReference->id(), '@label' => $XReference->label()]));
  }
}

/**
 * Drush callback for 'x-reference-get' drush command.
 */
function drush_x_reference_get_by_entity($direction) {
  $options['reference_type'] = drush_get_option('reference_ids');
  $options['entity_source'] = drush_get_option('entity_source');
  $options['entity_type'] = drush_get_option('entity_type');
  $options['entity_ids'] = drush_get_option('entity_ids');

  if ($options['entity_ids']) {
    $entityIds =  array_filter(explode(',', $options['entity_ids']));
  }
  else {
    $entityIds = [];
  }

  $XReferencedEntities = \Drupal::entityTypeManager()
    ->getStorage(XReferencedEntity::ENTITY_TYPE)
    ->loadByProperties(array_filter([
      'type' => $options['reference_type'],
      'entity_source' => $options['entity_source'],
      'entity_type' => $options['entity_type'],
      'entity_id' => $entityIds,
    ]));

  /** @var XReference[] $XReferences */
  if ($XReferencedEntities) {
    $propertyToSearch = $direction === 'source'
      ? 'source_entity'
      : 'target_entity';
    $XReferences = \Drupal::entityTypeManager()
      ->getStorage(XReference::ENTITY_TYPE)
      ->loadByProperties([
        $propertyToSearch => array_map(
          function (XReferencedEntity $XReferencedEntity) {
            return $XReferencedEntity->id();
          },
          $XReferencedEntities
        ),
      ]);
  }
  else {
    $XReferences = [];
  }

  if ($XReferences) {
    $table = [
      [
        'id' => dt('Id'),
        'type' => dt('Type'),
        'source' => dt('Source'),
        'target' => dt('Target'),
      ]
    ];
    foreach ($XReferences as $XReference) {
      $source = $XReference->getSourceEntity();
      $target = $XReference->getTargetEntity();
      $table[] = [
        'id' => $XReference->id(),
        'type' => $XReference->bundle(),
        'source' => $source ? $source->label() : 'NULL',
        'target' => $target ? $target->label() : 'NULL',
      ];
    }
    drush_print_table($table, TRUE);
  }
  else {
    drush_print('No X-references found for passed params');
  }
}

/**
 * @param string $paramValue
 *
 * @return int[]
 */
function _x_reference_drush_handle_ids_param($paramValue) {
  return array_filter(array_map(
    function ($item) {
      return (int) $item;
    },
    explode(',', $paramValue)
  ));
}

