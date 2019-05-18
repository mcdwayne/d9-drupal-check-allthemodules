<?php

/**
 * @file
 * Drush integration for the content serialization module.
 */

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\contentserialize\Destination\FileDestination;
use Drupal\contentserialize\Source\FileSource;
use Drupal\contentserialize\Traversables;
use Drupal\contentserialize\Utility;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drush\Log\LogLevel;

/**
 * Implements hook_drush_command().
 *
 * Provides content serialization drush commands.
 *
 * @deprecated This is for drush 8 and Drupal 8.3-.
 *
 * @see \Drupal\contentserialize\Commands\ContentSerializeCommands
 */
function contentserialize_drush_command() {
  $destination = ['destination' => dt('Folder to export to; you can also use the environment variable CONTENTSERIALIZE_EXPORT_DESTINATION; defaults to the current directory')];
  $format = ['format' => dt("The serialization format")];
  $exclude = ['exclude' => [
    'description' => dt('Entity types and/or bundles to exclude'),
    'example-value' => 'node:blog:page,user',
  ]];

  $items['contentserialize-export'] = [
    'description' => dt('Exports a single entity'),
    'arguments' => [
      'entity_type' => dt('The entity type to export.'),
      'entity_id' => dt('The ID of the entity to export.'),
    ],
    'options' => $destination + $format,
    'aliases' => ['cse'],
    'required-arguments' => 2,
  ];

  $items['contentserialize-export-referenced'] = [
    'description' => dt('Exports an entity and any others that reference it'),
    'arguments' => [
      'entity_type' => dt('The entity type to export.'),
      'entity_id' => dt('The ID of the entity to export.'),
    ],
    'options' => $exclude + $destination + $format,
    'aliases' => ['cser'],
    'required-arguments' => 2,
  ];

  $items['contentserialize-export-all'] = [
    'description' => dt('Exports all content from any appropriate entity types.'),
    'options' => $exclude + $destination + $format,
    'aliases' => ['csea'],
    'examples' => array(
      'drush csea' => 'Export all content entities on the site into the current directory.',
      'drush csea --destination=/path/to/content' => 'Export all content entities into the specified directory.',
      'drush csea --exclude=taxonomy_term' => 'Export all content entities except taxonomy terms.',
      'drush csea --exclude=node:page:blog' => "Export all content entities except the node bundles 'page' and 'blog'.",
      'drush csea --exclude=node:page,user' => "Export all content entities except the node bundle 'page' and users."
    ),
  ];

  $items['contentserialize-import'] = [
    'description' => dt('Imports content from a folder.'),
    'options' => [
      'source' =>  [
        'description' => dt('Folder(s) to import from in a comma-separated list; you can also use the environment variable CONTENTSERIALIZE_IMPORT_SOURCE; defaults to the current directory'),
        'example-value' => '/path/1,/path/2',
      ]
    ],
    'aliases' => ['csi'],
    'examples' => array(
      'drush csi --source=/tmp/import' => 'Import all content in /tmp/import.',
    ),
  ];

  return $items;
}

/**
 * Exports a single entity.
 *
 * @param string $entity_type_id
 *   The entity type ID.
 * @param int|string $entity_id
 *   The entity ID to export.
 */
function drush_contentserialize_export($entity_type_id, $entity_id) {
  $entity = _contentserialize_drush_load_content_entity($entity_type_id, $entity_id);
  if (!$entity) {
    return;
  }

  try {
    /** @var \Drupal\contentserialize\ExporterInterface $exporter */
    $exporter = \Drupal::service('contentserialize.exporter');
    $destination = _contentserialize_drush_get_export_destination();
    list($format, $context) = _contentserialize_drush_get_format_and_context();
    $destination->save($exporter->export($entity, $format, $context));
  }
  catch (\Exception $e) {
    drush_set_error('CONTENTSERIALIZE_EXPORT_FAILURE', $e->getMessage());
  }
}

/**
 * Exports an entity and any others that reference it.
 *
 * @param string $entity_type_id
 *   The entity type to export.
 * @param int|string $entity_id
 *   The ID of the entity to export.
 */
function drush_contentserialize_export_referenced($entity_type_id, $entity_id) {
  $entity = _contentserialize_drush_load_content_entity($entity_type_id, $entity_id);
  if (!$entity) {
    return;
  }

  try {
    /** @var \Drupal\contentserialize\ExporterInterface $exporter */
    $exporter = \Drupal::service('contentserialize.exporter');
    $destination = _contentserialize_drush_get_export_destination();
    $entities = Utility::enumerateEntitiesAndDependencies([$entity]);
    $excluded = _contentserialize_drush_get_excluded();
    if ($excluded) {
      // Filter out entity types and bundles specified in the exclude option.
      $entities = Traversables::filter($entities, function (ContentEntityInterface $entity) use ($excluded) {
        $entity_type = $entity->getEntityTypeId();
        $bundle = $entity->bundle();
        $type_allowed = !in_array($entity_type, $excluded['entity_type']);
        $bundle_allowed = empty($excluded['bundles'][$entity_type]) || !in_array($bundle, $excluded['bundles'][$entity_type]);
        return $type_allowed && $bundle_allowed;
      });
    }
    list($format, $context) = _contentserialize_drush_get_format_and_context();
    $destination->saveMultiple($exporter->exportMultiple($entities, $format, $context));
  }
  catch (\Exception $e) {
    drush_set_error('CONTENTSERIALIZE_EXPORT_FAILURE', $e->getMessage());
  }
}

/**
 * Exports all content optionally filtered by entity type and/or bundle.
 */
function drush_contentserialize_export_all() {
  // Filter out any non-content entity types.
  /** @var \Drupal\Core\Entity\EntityTypeInterface[] $definitions */
  $definitions = \Drupal::entityTypeManager()->getDefinitions();
  $definitions = array_filter($definitions, function (EntityTypeInterface $definition) {
    return is_a($definition->getClass(), ContentEntityInterface::class, TRUE);
  });
  // Filter out entire entity types specified in the exclude option.
  $excluded = _contentserialize_drush_get_excluded();
  if ($excluded) {
    $definitions = array_filter($definitions, function (EntityTypeInterface $definition) use ($excluded) {
      return !in_array($definition->id(), $excluded['entity_type']);
    });
  }
  /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info */
  $bundle_info = \Drupal::service('entity_type.bundle.info');
  try {
    $destination = _contentserialize_drush_get_export_destination();
    /** @var \Drupal\contentserialize\ExporterInterface $exporter */
    $exporter = \Drupal::service('contentserialize.exporter');
    /** @var \Drupal\bulkentity\EntityLoaderInterface $entity_loader */
    $entity_loader = \Drupal::service('bulkentity.loader');

    drush_print(dt("Exporting..."), 0, STDERR);
    foreach ($definitions as $entity_type_id => $definition) {
      // Filter out bundles specified in the exclude option.
      $bundles = NULL;
      if (!empty($excluded['bundle'][$entity_type_id])) {
        $all_bundles = array_keys($bundle_info->getBundleInfo($entity_type_id));
        $bundles = array_diff($all_bundles, $excluded['bundle'][$entity_type_id]);
      }
      $entities = $entity_loader->byEntityType(50, $entity_type_id, $bundles);
      list($format, $context) = _contentserialize_drush_get_format_and_context();
      $destination->saveMultiple($exporter->exportMultiple($entities, $format, $context));
      drush_print(' - ' . $definition->getLabel(), 0, STDERR);
    }
    drush_print(dt("Completed"), 0, STDERR);
  }
  catch (\Exception $e) {
    drush_set_error('CONTENTSERIALIZE_EXPORT_FAILURE', $e->getMessage());
  }
}

/**
 * Imports content from a folder.
 *
 * @todo Provide more detailed output.
 */
function drush_contentserialize_import() {
  /** @var \Drupal\contentserialize\ImporterInterface $importer */
  $importer = \Drupal::service('contentserialize.importer');
  try {
    $sources = _contentserialize_drush_get_import_sources();
    // Ensure the same entity isn't returned twice and that earlier entities
    // take priority over later ones.
    $merged = Traversables::uniqueByKey(Traversables::merge(...$sources));
    $result = $importer->import($merged);
  }
  catch (\Exception $e) {
    drush_set_error('CONTENTSERIALIZE_IMPORT_FAILURE', $e->getMessage());
    return;
  }
  if ($result->getFailures()) {
    drush_set_error('CONTENTSERIALIZE_IMPORT_FAILURE', dt("There were some errors on import."), LogLevel::ERROR);
  }
  else {
    drush_log(dt("Import completed successfully."));
  }
}

/**
 * Get excluded entity types and bundles.
 *
 * It tries to read --exclude first and falls back to configuration.
 *
 * @return array
 *   An associative array keyed by:
 *   - entity_type: An array of entity type IDs to be fully excluded.
 *   - bundle: An associative array of bundles to exclude, keyed by entity type
 *     ID.
 *   It will be empty if there are no exclude options specified anywhere.
 */
function _contentserialize_drush_get_excluded() {
  /** @var \Drupal\contentserialize\Commands\ContentSerializeOptionsProvider $options_provider */
  $options_provider = \Drupal::service('contentserialize.cli.options_provider');
  $options = ['exclude' => drush_get_option('exclude')];
  return $options_provider->getExcluded($options);
}

/**
 * Get the export destination.
 *
 * It tries to read from the following locations in order before falling back to
 * the current directory if they all fail.
 * 1. the --destination option;
 * 2. the environment variable CONTENTSERIALIZE_EXPORT_DESTINATION;
 * 3. contentserialize.settings.file.defaults.export_destination.
 *
 * @return \Drupal\contentserialize\Destination\DestinationInterface
 */
function _contentserialize_drush_get_export_destination() {
  /** @var \Drupal\contentserialize\Commands\ContentSerializeOptionsProvider $options_provider */
  $options_provider = \Drupal::service('contentserialize.cli.options_provider');
  $folder = $options_provider->getExportFolder(['destination' => drush_get_option('destination')]);
  return new FileDestination($folder);
}

/**
 * Get the import sources.
 *
 * It tries to read from the following locations in order before falling back to
 * the current directory if they all fail.
 * 1. the --source option;
 * 2. the environment variable CONTENTSERIALIZE_IMPORT_SOURCE;
 * 3. contentserialize.settings.file.defaults.import_source.
 *
 * @return \Drupal\contentserialize\Source\SourceInterface[]
 *   An array of import sources in priority order (an entity will only be
 *   imported the first time it's encountered).
 */
function _contentserialize_drush_get_import_sources() {
  /** @var \Drupal\contentserialize\Commands\ContentSerializeOptionsProvider $options_provider */
  $options_provider = \Drupal::service('contentserialize.cli.options_provider');
  $options = ['source' => drush_get_option('source')];
  return array_map(function ($source) {
    return new FileSource($source);
  }, $options_provider->getImportFolders($options));
}

/**
 * Get the serialization format and context array.
 *
 * It reads from the following locations:
 * 1. the --format option
 * 2. contentserialize.settings.drush.defaults.format
 *
 * @return array
 *   An indexed array containing:
 *   - the format
 *   - the context array
 */
function _contentserialize_drush_get_format_and_context() {
  /** @var \Drupal\contentserialize\Commands\ContentSerializeOptionsProvider $options_provider */
  $options_provider = \Drupal::service('contentserialize.cli.options_provider');
  $options = ['format' => drush_get_option('format')];
  return $options_provider->getFormatAndContext($options);
}

/**
 * Try to load the specified content entity, logging any errors with drush.
 *
 * @param string $entity_type_id
 *   The entity type ID.
 * @param int|string $entity_id
 *   The entity ID.
 *
 * @return \Drupal\Core\Entity\ContentEntityInterface|false
 *   The loaded content entity, or FALSE on failure.
 */
function _contentserialize_drush_load_content_entity($entity_type_id, $entity_id) {
  try {
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
  }
  catch (PluginNotFoundException $e) {
    return drush_set_error('CONTENT_SERIALIZE_NO_ENTITY_TYPE', dt("Entity type @entity_type_id doesn't exist.", ['@entity_type_id' => $entity_type_id]));
  }
  $entity = $storage->load($entity_id);
  if (!$entity) {
    return drush_set_error('CONTENTSERIALIZE_NO_ENTITY', dt("Couldn't load @entity_type @entity_id: entity doesn't exist.", ['@entity_type' => $entity_type_id, '@entity_id' => $entity_id]));
  }
  if (!$entity instanceof ContentEntityInterface) {
    return drush_set_error('CONTENTSERIALIZE_NOT_CONTENT_ENTITY', dt("contentserialize can only export content entities."));
  }

  return $entity;
}
