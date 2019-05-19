<?php

namespace Drupal\yaml_content\ContentLoader;

/**
 * Defines a common interface for content loader implementations.
 */
interface ContentLoaderInterface {

  /**
   * Parse the given yaml content file into an array.
   *
   * @param string $content_file
   *   A file name for the content file to be loaded.
   *
   * @return array
   *   The parsed content array from the file.
   */
  public function parseContent($content_file);

  /**
   * Load a batch of content files.
   *
   * @param array $files
   *   An array of file names for loading content from.
   * @param array $options
   *   An array of configuration options to be used during this import.
   *
   * @return array
   *   An associative array of loaded content items keyed by file name.
   */
  public function loadContentBatch(array $files, array $options = []);

  /**
   * Load all demo content for a set of parsed data.
   *
   * @param array $content_data
   *   The parsed content structure to be imported.
   *
   * @return array
   *   An array of loaded entities from the content data.
   *
   * @see \Drupal\yaml_content\ContentLoader\ContentLoaderInterface::parseContent()
   */
  public function loadContent(array $content_data);

  /**
   * Build an entity from the provided content data.
   *
   * @param string $entity_type
   *   The entity type machine name to be created.
   * @param array $content_data
   *   Parameters:
   *     - `entity`: (required) The entity type machine name.
   *     - `bundle`: (required) The bundle machine name.
   *     - Additional field and property data keyed by field or property name.
   * @param array $context
   *   Contextual data available for more specific entity creation requirements.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A loaded entity object ready to be saved.
   */
  public function buildEntity($entity_type, array $content_data, array &$context);

}
