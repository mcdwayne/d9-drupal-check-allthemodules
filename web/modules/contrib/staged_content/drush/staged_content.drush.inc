<?php

/**
 * @file
 * Drush integration for the default_content module.
 */

use Symfony\Component\Yaml\Yaml;
use Drupal\staged_content\Storage\JsonFileStorage;

/**
 * Implements hook_drush_command().
 */
function staged_content_drush_command() {
  $items['staged-content-export-type'] = [
    'description' => dt('Exports all entities of a given type'),
    'arguments' => [
      'entity_type' => dt('The entity type to export.'),
    ],
    'options' => [
      'folder' => dt('Write out the exported content to a folder'),
      'include-id' => dt('Should the id be included in the export (useful for nodes etc that have links to various other items in the site.), defaults to false.'),
    ],
    'aliases' => ['scet'],
    'required-arguments' => 1,
  ];
  $items['staged-content-export'] = [
    'description' => dt('Exports a full set based on a number of extra criteria in a config file.'),
    'arguments' => [
      'config_file' => dt('The yml config file containing the config data for the set.'),
    ],
    'options' => [
      'folder' => dt('Write out the exported content to a folder'),
    ],
    'aliases' => ['sce'],
    'required-arguments' => 1,
  ];
  $items['staged-content-import'] = [
    'description' => dt('Imports all entities in a given storage folder'),
    'arguments' => [
      'source_folder' => dt('Source folder with all the data.'),
    ],
    'options' => [
      'markers' => dt('comma separated list of allowed markers.'),
    ],
    'aliases' => ['sci'],
    'required-arguments' => 1,
  ];
  return $items;
}

/**
 * Exports a piece of content into the stdout or into a file.
 *
 * @param string $configFile
 *   The config file in yaml format containing all the information.
 *
 * @throws \Exception
 *   If the config file could not be found or is invalid.
 */
function drush_staged_content_export($configFile) {

  if (!file_exists($configFile)) {
    throw new \Exception(sprintf('Config file %s could not be found', $configFile));
  }

  $config = Yaml::parse(file_get_contents($configFile));

  // Generate a manifest file with all the data to export and the correct
  // level to export it to.
  /** @var \Drupal\staged_content\ManifestWriter $manifestWriter */
  $manifestWriter = \Drupal::service('staged_content.manifest_writer');
  $manifestWriter->write(getcwd() . '/manifest.yml', $config);

  // Write out all the items to the location indicated by their marker.
  $outputFolder = isset($outputFolder) ? $outputFolder : getcwd() . '/staged-content-output/MARKER_NAME';
  $storageHandler = new JsonFileStorage($outputFolder);

  /** @var \Drupal\staged_content\LayeredExporter $exporter */
  $exporter = \Drupal::service('staged_content.exporter');
  $exporter->setStorageHandler($storageHandler);

  // @TODO Clean this up.
  foreach ($config['entity_types'] as $entityTypeInfo) {
    $includeId = isset($entityTypeInfo['includeId']) && $entityTypeInfo['includeId'];
    $exporter->setIncludeId($includeId);

    // @TODO Get the included references from the config file.
    $exporter->exportType($entityTypeInfo['entityType'],
      ['file', 'media', 'paragraph'],
      $config['markers']
    );
  }
}

/**
 * Exports a piece of content into the stdout or into a file.
 *
 * @param string $entityTypeId
 *   The entity type ID.
 */
function drush_staged_content_export_type($entityTypeId) {
  $outputFolder = drush_get_option('folder');
  // @TODO Clean up the output dir default.
  $outputFolder = isset($outputFolder) ? $outputFolder : getcwd() . '/staged-content-output';
  $storageHandler = new JsonFileStorage($outputFolder);

  /** @var \Drupal\staged_content\LayeredExporter $exporter */
  $exporter = \Drupal::service('staged_content.exporter');
  $exporter->setStorageHandler($storageHandler);

  if (drush_get_option('include-id')) {
    $exporter->setIncludeId(TRUE);
  }

  if (drush_get_option('include-references')) {
    $includedReferences = explode(',', drush_get_option('include-references'));
  }
  else {
    $includedReferences = ['file', 'media', 'paragraph'];
  }

  $exporter->exportType($entityTypeId, $includedReferences);
}

/**
 * Exports a piece of content into the stdout or into a file.
 *
 * @param string $sourceDir
 *   Source directory where all the files can be found.
 */
function drush_staged_content_import($sourceDir) {
  $markers = drush_get_option('markers');
  if (isset($markers)) {
    $markers = array_filter(explode(',', $markers));
  }
  $storageHandler = new JsonFileStorage($sourceDir, $markers);

  /** @var \Drupal\staged_content\LayeredImporter $importer */
  $importer = \Drupal::service('staged_content.importer');
  $importer->importContent($storageHandler);
}
