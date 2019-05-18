<?php

namespace Drupal\contentserialize\Commands;

use Drupal\bulkentity\EntityLoaderInterface;
use Drupal\contentserialize\Destination\FileDestination;
use Drupal\contentserialize\ExporterInterface;
use Drupal\contentserialize\ImporterInterface;
use Drupal\contentserialize\Source\FileSource;
use Drupal\contentserialize\Traversables;
use Drupal\contentserialize\Utility;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * Provides drush 9 commands for Content Serialization.
 *
 * D8 standards are not to translate exception messages, but many/all used here
 * are user-facing, like eg. \Drush\Commands\sql\SqlSyncCommands::validate(), so
 * they're passed through dt().
 */
class ContentSerializeCommands extends DrushCommands {

  /**
   * The options provider.
   *
   * @var \Drupal\contentserialize\Commands\ContentSerializeOptionsProvider
   */
  protected $optionsProvider;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The bulk entity loader.
   *
   * @var \Drupal\bulkentity\EntityLoaderInterface
   */
  protected $bulkLoader;

  /**
   * The content exporter.
   *
   * @var \Drupal\contentserialize\ExporterInterface
   */
  protected $exporter;

  /**
   * The content importer.
   *
   * @var \Drupal\contentserialize\ImporterInterface
   */
  protected $importer;

  /**
   * Create the content serialization commands object.
   *
   * @param \Drupal\contentserialize\Commands\ContentSerializeOptionsProvider $options_provider
   */
  public function __construct(
    $options_provider,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $bundle_info,
    EntityLoaderInterface $bulk_loader,
    ImporterInterface $importer,
    ExporterInterface $exporter
  ) {
    parent::__construct();
    $this->optionsProvider = $options_provider;
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $bundle_info;
    $this->bulkLoader = $bulk_loader;
    $this->importer = $importer;
    $this->exporter = $exporter;
  }

  /**
   * Exports a single entity
   *
   * @param $entity_type
   *   The entity type to export.
   * @param $entity_id
   *   The ID of the entity to export.
   * @param array $options An associative array of options whose values come
   *   from cli, aliases, config, etc.
   *
   * @option destination
   *   Folder to export to; you can also use the environment variable CONTENTSERIALIZE_EXPORT_DESTINATION; defaults to the current directory
   * @option format
   *   The serialization format
   *
   * @command contentserialize:export
   * @aliases cse,contentserialize-export
   * 
   * @throws \Exception
   *   On errors.
   */
  public function export($entity_type, $entity_id, array $options = ['destination' => self::REQ, 'format' => self::REQ]) {
    $entity = $this->loadContentEntity($entity_type, $entity_id);
    $destination = $this->getExportDestination($options);
    list($format, $context) = $this->optionsProvider->getFormatAndContext($options);
    $destination->save($this->exporter->export($entity, $format, $context));
  }

  /**
   * Exports an entity and any others that reference it
   *
   * @param $entity_type
   *   The entity type to export.
   * @param $entity_id
   *   The ID of the entity to export.
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option exclude
   *   Entity types and/or bundles to exclude
   * @option destination
   *   Folder to export to; you can also use the environment variable CONTENTSERIALIZE_EXPORT_DESTINATION; defaults to the current directory
   * @option format
   *   The serialization format
   *
   * @command contentserialize:export-referenced
   * @aliases cser,contentserialize-export-referenced
   *
   * @throws \Exception
   *   On error
   */
  public function exportReferenced($entity_type, $entity_id, array $options = ['exclude' => self::REQ, 'destination' => self::REQ, 'format' => self::REQ]) {
    $entity = $this->loadContentEntity($entity_type, $entity_id);

    $destination = $this->getExportDestination($options);
    $entities = Utility::enumerateEntitiesAndDependencies([$entity]);
    $excluded = $this->optionsProvider->getExcluded($options);
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
    list($format, $context) = $this->optionsProvider->getFormatAndContext($options);
    $destination->saveMultiple($this->exporter->exportMultiple($entities, $format, $context));
  }

  /**
   * Exports all content from any appropriate entity types.
   *
   * @param array $options
   *    An associative array of options whose values come from cli, aliases,
   *    config, etc.
   *
   * @option exclude
   *   Entity types and/or bundles to exclude
   * @option destination
   *   Folder to export to; you can also use the environment variable CONTENTSERIALIZE_EXPORT_DESTINATION; defaults to the current directory
   * @option format
   *   The serialization format
   * @usage drush csea
   *   Export all content entities on the site into the current directory.
   * @usage drush csea --destination=/path/to/content
   *   Export all content entities into the specified directory.
   * @usage drush csea --exclude=taxonomy_term
   *   Export all content entities except taxonomy terms.
   * @usage drush csea --exclude=node:page:blog
   *   Export all content entities except the node bundles 'page' and 'blog'.
   * @usage drush csea --exclude=node:page,user
   *   Export all content entities except the node bundle 'page' and users.
   *
   * @command contentserialize:export-all
   * @aliases csea,contentserialize-export-all
   */
  public function exportAll(array $options = ['exclude' => self::REQ, 'destination' => self::REQ, 'format' => self::REQ]) {
    // Filter out any non-content entity types.
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $definitions */
    $definitions = $this->entityTypeManager->getDefinitions();
    $definitions = array_filter($definitions, function (EntityTypeInterface $definition) {
      return is_a($definition->getClass(), ContentEntityInterface::class, TRUE);
    });
    // Filter out entire entity types specified in the exclude option.
    $excluded = $this->optionsProvider->getExcluded($options);
    if ($excluded) {
      $definitions = array_filter($definitions, function (EntityTypeInterface $definition) use ($excluded) {
        return !in_array($definition->id(), $excluded['entity_type']);
      });
    }
    $destination = $this->getExportDestination($options);
    list($format, $context) = $this->optionsProvider->getFormatAndContext($options);

    $this->output()->writeln(dt("Exporting..."));
    foreach ($definitions as $entity_type_id => $definition) {
      // Filter out bundles specified in the exclude option.
      $bundles = NULL;
      if (!empty($excluded['bundle'][$entity_type_id])) {
        $all_bundles = array_keys($this->bundleInfo->getBundleInfo($entity_type_id));
        $bundles = array_diff($all_bundles, $excluded['bundle'][$entity_type_id]);
      }
      // @todo Make batch size configurable.
      $entities = $this->bulkLoader->byEntityType(50, $entity_type_id, $bundles);
      $destination->saveMultiple($this->exporter->exportMultiple($entities, $format, $context));
      $this->output()->writeln(' - ' . (string) $definition->getLabel());
    }
    $this->output()->writeln(dt("Completed"));
  }

  /**
   * Imports content from a folder.
   *
    * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
   * @option source
   *   Folder(s) to import from in a comma-separated list; you can also use the environment variable CONTENTSERIALIZE_IMPORT_SOURCE; defaults to the current directory
   * @usage drush csi --source=/tmp/import
   *   Import all content in /tmp/import.
   *
   * @command contentserialize:import
   * @aliases csi,contentserialize-import
   *
   * @throws \Exception
   *   On import errors.
   */
  public function import(array $options = ['source' => self::REQ]) {
    $sources = $this->getImportSources($options);
    // Ensure the same entity isn't returned twice and that earlier entities
    // take priority over later ones.
    $merged = Traversables::uniqueByKey(Traversables::merge(...$sources));
    $result = $this->importer->import($merged);
    if ($result->getFailures()) {
      throw new \Exception(dt("There were errors on import."));
    }
    else {
      $this->io()->writeln(dt("Import completed successfully."));
    }
  }

  /**
   * Try to load the specified content entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int|string $entity_id
   *   The entity ID.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|false
   *   The loaded content entity, or FALSE on failure.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   If the entity type isn't valid.
   * @throws \Exception
   *   On error
   */
  protected function loadContentEntity($entity_type_id, $entity_id) {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $class = $storage->getEntityType()->getClass();
    if (!is_subclass_of($class, ContentEntityInterface::class, TRUE)) {
      throw new \Exception(dt("Content serialization can only export content entities."));
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $storage->load($entity_id);
    if (!$entity) {
      throw new \Exception(dt("Couldn't load @entity_type with ID @entity_id", ['@entity_type' => $entity_type_id, '@entity_id' => $entity_id]));
    }

    return $entity;
  }

  /**
   * Get the export destination.
   *
   * @param array $options
   *   The command's options array; the 'format' key will be used if present.
   *
   * @return \Drupal\contentserialize\Destination\DestinationInterface
   *
   * @see \Drupal\contentserialize\Commands\ContentSerializeOptionsProvider::getExportFolder()
   */
  protected function getExportDestination($options) {
    return new FileDestination($this->optionsProvider->getExportFolder($options));
  }

  /**
   * Get the import sources.
   *
   * @param array $options
   *   The command's options array; the 'source' key will be used if present.
   *
   * @return \Drupal\contentserialize\Source\SourceInterface[]
   *   An array of import sources in priority order (an entity will only be
   *   imported the first time it's encountered).
   *
   * @see \Drupal\contentserialize\Commands\ContentSerializeOptionsProvider::getImportFolders()
   */
  protected function getImportSources(array $options) {
    return array_map(function ($source) {
      return new FileSource($source);
    }, $this->optionsProvider->getImportFolders($options));
  }

}
