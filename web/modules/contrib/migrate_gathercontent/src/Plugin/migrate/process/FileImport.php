<?php

// TODO: Need to clean up this file.

namespace Drupal\migrate_gathercontent\Plugin\migrate\process;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Plugin\migrate\process\Download;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Component\Render\PlainTextOutput;

/**
 * Imports a file from GatherContent. .
 *
 * Files will be downloaded or copied from the source if necessary and a file
 * entity will be created for it. The file can be moved, reused, or set to be
 * automatically renamed if a duplicate exists.
 *
 * Required configuration keys:
 * - source: The source path or URI, e.g. '/path/to/foo.txt' or
 *   'public://bar.txt'.
 *
 * Optional configuration keys:
 * - destination: (recommended) The destination path or URI, example:
 *   '/path/to/bar/' or 'public://foo.txt'. To provide a directory path (to
 *   which the file is saved using its original name), a trailing slash *must*
 *   be used to differentiate it from being a filename. If no trailing slash
 *   is provided the path will be assumed to be the destination filename.
 *   Defaults to "public://".
 * - uid: The uid to attribute the file entity to. Defaults to 0
 * - move: Boolean, if TRUE, move the file, otherwise copy the file. Only
 *   applies if the source file is local. If the source file is remote it will
 *   be copied. Defaults to FALSE.
 * - rename: Boolean, if TRUE, rename the file by appending a number
 *   until the name is unique. Defaults to FALSE.
 * - reuse: Boolean, if TRUE, reuse the current file in its existing
 *   location rather than move/copy/rename the file. Defaults to FALSE.
 * - skip_on_missing_source: (optional) Boolean, if TRUE, this field will be
 *   skipped if the source file is missing (either not available locally or 404
 *   if it's a remote file). Otherwise, the row will fail with an error. Note
 *   that if you are importing a lot of remove files, this check will greatly
 *   reduce the speed of your import as it requires an http request per file to
 *   check for existence. Defaults to FALSE.
 * - skip_on_error: (optional) Boolean, if TRUE, this field will be skipped
 *   if any error occurs during the file import (including missing source
 *   files). Otherwise, the row will fail with an error. Defaults to FALSE.
 * - id_only: (optional) Boolean, if TRUE, the process will return just the id
 *   instead of a entity reference array. Useful if you want to manage other
 *   sub-fields in your migration (see example below).
 *
 * The destination and uid configuration fields support copying destination
 * values. These are indicated by a starting @ sign. Values using @ must be
 * wrapped in quotes. (the same as it works with the 'source' key).
 *
 * @see Drupal\migrate\Plugin\migrate\process\Get
 *
 * Example:
 *
 * @code
 * destination:
 *   plugin: entity:node
 * source:
 *   # assuming we're using a source plugin that lets us define fields like this
 *   fields:
 *     -
 *       name: file
 *       label: 'Some file'
 *       selector: /file
 *     -
 *       name: image
 *       label: 'Main Image'
 *       selector: /image
 *   constants:
 *     file_destination: 'public://path/to/save/'
 * process:
 *   uid:
 *     plugin: default_value
 *     default_value: 1
 *   # Simple file import
 *   field_file:
 *     plugin: file_import
 *     source: file
 *     destination: constants/file_destination
 *     uid: @uid
 *     skip_on_missing_source: true
 *   # Custom field attributes
 *   field_image/target_id:
 *     plugin: file_import
 *     source: image
 *     destination: constants/file_destination
 *     uid: @uid
 *     id_only: true
 *   field_image/alt: image
 *
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "gathercontent_file_import"
 * )
 */
class FileImport extends Download implements ContainerFactoryPluginInterface {


  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The destination type.
   *
   * @var string
   */
  protected $destinationEntityType;

  /**
   * The destination bundle.
   *
   * @var string|bool
   */
  protected $destinationBundleKey;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MigrationInterface $migration, EntityFieldManager $entityFieldManager, EntityTypeManager $entityTypeManager, FileSystemInterface $file_system, Client $http_client) {
    $configuration += [
      'uid' => NULL,
      'destination_field' => NULL,
      'id_only' => FALSE,
    ];

    $this->migration = $migration;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;
    $pluginIdParts = explode(':', $this->migration->getDestinationPlugin()->getPluginId());
    $this->destinationEntityType = empty($pluginIdParts[1]) ?: $pluginIdParts[1];
    $this->destinationBundleKey = !$this->destinationEntityType ?: $this->entityTypeManager->getDefinition($this->destinationEntityType)->getKey('bundle');
    parent::__construct($configuration, $plugin_id, $plugin_definition,$file_system,$http_client);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $uid = $this->getPropertyValue($this->configuration['uid'], $row) ?: 0;
    $id_only = $this->configuration['id_only'];

    list($source, $destination) = $value;

    if ($this->configuration['destination_field']) {
      $destination_property = $this->configuration['destination_field'];
    }
    // If file destination is empty and field is a file/image field.
    // Grab the destination from that field's config.
    $parts = explode('/', $destination_property);
    $destinationProperty = reset($parts);
    $destinationEntityBundle = $this->migration->getProcess()[$this->destinationBundleKey][0]['default_value'];
    $fieldConfig = $this->entityFieldManager->getFieldDefinitions($this->destinationEntityType, $destinationEntityBundle)[$destinationProperty]->getConfig($destinationEntityBundle);

    switch ($fieldConfig->getType()) {
      case 'file':
      case 'image':
        $settings = $fieldConfig->getSettings();
        $path = trim($settings['file_directory'], '/');
        $path = PlainTextOutput::renderFromHtml(\Drupal::token()->replace($path, array()));
        $path = $settings['uri_scheme'] . '://' . $path;
        $destination = $path. '/' . $destination;
        break;
    }

    $value = [
      $source,
      $destination
    ];

    $final_destination = parent::transform($value, $migrate_executable, $row, $destination_property);

    if ($final_destination) {
      // Create a file entity.
      $file = File::create([
        'uri' => $final_destination,
        'uid' => $uid,
        'status' => FILE_STATUS_PERMANENT,
      ]);
      $file->save();
      return $id_only ? $file->id() : ['target_id' => $file->id()];
    }

  }
  /**
   * Gets a value from a source or destination property.
   *
   * Code is adapted from Drupal\migrate\Plugin\migrate\process\Get::transform()
   */
  protected function getPropertyValue($property, $row) {
    if ($property || (string) $property === '0') {
      $is_source = TRUE;
      if ($property[0] == '@') {
        $property = preg_replace_callback('/^(@?)((?:@@)*)([^@]|$)/', function ($matches) use (&$is_source) {
          // If there are an odd number of @ in the beginning, it's a
          // destination.
          $is_source = empty($matches[1]);
          // Remove the possible escaping and do not lose the terminating
          // non-@ either.
          return str_replace('@@', '@', $matches[2]) . $matches[3];
        }, $property);
      }
      if ($is_source) {
        return $row->getSourceProperty($property);
      }
      else {
        return $row->getDestinationProperty($property);
      }
    }
    return FALSE;
  }

  /**
   * Determines how to handle file conflicts.
   *
   * @return int
   *   FILE_EXISTS_REPLACE (default), FILE_EXISTS_RENAME, or FILE_EXISTS_ERROR
   *   depending on the current configuration.
   */
  protected function getOverwriteMode() {
    if (!empty($this->configuration['rename'])) {
      return FILE_EXISTS_RENAME;
    }
    if (!empty($this->configuration['reuse'])) {
      return FILE_EXISTS_ERROR;
    }

    return FILE_EXISTS_REPLACE;
  }

  /**
   * Check if a path is a meant to be a directory.
   *
   * We're using a trailing slash to indicate the path is a directory. This is
   * so that we can create it if it doesn't exist. Without the trailing slash
   * there would be no reliable way to know whether or not the path is meant
   * to be the target filename since files don't technically _have_ to have
   * extensions, and directory names can contain periods.
   */
  protected function isDirectory($path) {
    return substr($path, -1) == '/';
  }

  /**
   * Build the destination filename.
   *
   * @param string $source
   *   The source URI.
   *
   * @param string $destination
   *   The destination URI.
   *
   * @return boolean
   *   Whether or not the file exists.
   */
  protected function getDestinationFilePath($source, $destination) {
    if ($this->isDirectory($destination)) {
      $parsed_url = parse_url($source);
      $filepath = $destination . drupal_basename($parsed_url['path']);
    }
    else {
      $filepath = $destination;
    }
    return $filepath;
  }

  /**
   * Check if a source exists.
   */
  protected function sourceExists($path) {
    if ($this->isLocalUri($path)) {
      return is_file($path);
    }
    else {
      try {
        \Drupal::httpClient()->head($path);
        return TRUE;
      }
      catch (ClientException $e) {
        return FALSE;
      }
      catch (ConnectException $e) {
        return FALSE;
      }
    }
  }

}
