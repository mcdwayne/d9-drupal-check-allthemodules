<?php

namespace Drupal\contentserialize\Commands;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides options for content serialization CLI commands.
 *
 * @see \Drupal\contentserialize\Commands\ContentSerializeCommands
 */
class ContentSerializeOptionsProvider {

  use StringTranslationTrait;

  /**
   * The name of the environment variable with the export destination.
   *
   * @string
   */
  const ENV_EXPORT_DESTINATION = 'CONTENTSERIALIZE_EXPORT_DESTINATION';

  /**
   * The name of the environment variable with the import source.
   *
   * Multiple sources can be separated by a comma.
   *
   * @string
   */
  const ENV_IMPORT_SOURCE = 'CONTENTSERIALIZE_IMPORT_SOURCE';

  /**
   * The configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * ContentSerializeOptionsProvider constructor.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The configuration object.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct($config, TranslationInterface $string_translation) {
    $this->config = $config;
    // @see \Drupal\Core\StringTranslation\StringTranslationTrait
    $this->stringTranslation = $string_translation;
  }

  /**
   * Get the serialization format and context array.
   *
   * It reads from the following locations:
   * 1. the --format option
   * 2. contentserialize.settings.drush.defaults.format
   *
   * @param array $options
   *   The command's options array; the 'format' key will be used if present.
   *
   * @return array
   *   An indexed array containing:
   *   - the format
   *   - the context array
   *
   * @throws \RuntimeException
   *   If a specified format doesn't exist.
   */
  public function getFormatAndContext($options) {
    if (!empty($options['format'])) {
      $format = $options['format'];
    }
    else {
      $format = $this->config->get('drush.defaults.format');
    }
    if (!$format) {
      // Drupal standards say not to have translatable exception messages, but
      // when used with drush they are the user-facing error messages. And this
      // particular message is tightly coupled to the logic in this method.
      throw new \RuntimeException($this->t("The format must either be passed in the command line or configured in @config_location.", ['@config_location' => 'contentserialize.settings.drush.defaults.format']));
    }

    $json_context = ['json_encode_options' => JSON_PRETTY_PRINT];
    $context_map = [
      'json' => $json_context,
      'hal_json' => $json_context,
    ];

    $context = !empty($context_map[$format]) ? $context_map[$format] : [];
    return [$format, $context];
  }

  /**
   * Get the export destination folder.
   *
   * It tries to read from the following locations in order before falling back to
   * the current directory if they all fail.
   * 1. the --destination option;
   * 2. the environment variable CONTENTSERIALIZE_EXPORT_DESTINATION;
   * 3. contentserialize.settings.file.defaults.export_destination.
   *
   * @param array $options
   *   The command's options array; the 'format' key will be used if present.
   *
   * @return string
   *   A relative or absolute path to the destination folder.
   */
  public function getExportFolder($options) {
    if (!empty($options['destination'])) {
      $folder = $options['destination'];
    }
    else {
      $folder = getenv(static::ENV_EXPORT_DESTINATION);
    }
    if (!$folder) {
      $folder = $this->config->get('file.defaults.export_destination');
    }
    return $folder ?: '.';
  }

  /**
   * Get excluded entity types and bundles.
   *
   * It tries to read --exclude first and falls back to configuration.
   *
   * @param array $options
   *   The command's options array; the 'format' key will be used if present.
   *
   * @return array
   *   An associative array keyed by:
   *   - entity_type: An array of entity type IDs to be fully excluded.
   *   - bundle: An associative array of bundles to exclude, keyed by entity type
   *     ID.
   *   It will be empty if there are no exclude options specified anywhere.
   */
  public function getExcluded(array $options) {
    if (!empty($options['exclude'])) {
      $exclude_option = $options['exclude'];
    }
    else {
      $exclude_option = $this->config->get('drush.defaults.exclude');
    }
    $excluded = [];
    if ($exclude_option) {
      $excluded = $this->parseExcludeOption($exclude_option);
    }
    return $excluded;
  }

  /**
   * Parse the exclude option.
   *
   * @param string $exclude_option
   *   The exclude option string.
   *
   * @return array
   *   An associative array keyed by:
   *   - entity_type: An array of entity type IDs to be fully excluded.
   *   - bundle: An associative array of bundles to exclude, keyed by entity type
   *     ID.
   *
   * @see \Drupal\contentserialize\Commands\ContentSerializeCommands::exportReferenced()
   * @see \Drupal\contentserialize\Commands\ContentSerializeCommands::exportAll()
   */
  protected function parseExcludeOption($exclude_option) {
    $excluded_entity_types = [];
    $excluded_bundles = [];

    foreach (explode(',', $exclude_option) as $value) {
      $pieces = explode(':', $value);
      // The first piece is the entity type ID.
      $entity_type_id = array_shift($pieces);
      // Any remaining pieces are bundles.
      if ($pieces) {
        $excluded_bundles[$entity_type_id] = $pieces;
      }
      else {
        $excluded_entity_types[] = $entity_type_id;
      }
    }

    return [
      'entity_type' => $excluded_entity_types,
      'bundle' => $excluded_bundles,
    ];
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
   * @param array $options
   *   The command's options array; the 'source' key will be used if present.
   *
   * @return \Drupal\contentserialize\Source\SourceInterface[]
   *   An array of import sources in priority order (an entity will only be
   *   imported the first time it's encountered).
   */
  public function getImportFolders(array $options) {
    if (!empty($options['source'])) {
      $folders = $options['source'];
    }
    else {
      $folders = getenv(static::ENV_IMPORT_SOURCE);
    }
    if ($folders) {
      $folders = explode(',', $folders);
    }
    else {
      $folders = $this->config->get('file.defaults.import_sources');
    }
    if (!$folders) {
      $folders = ['.'];
    }
    return $folders;
  }

}
