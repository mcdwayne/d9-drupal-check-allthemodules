<?php

namespace Drupal\search_api_swiftype\SwiftypeEngine;

use Drupal\search_api_swiftype\SwiftypeEntityInterface;

/**
 * Defines the interface for SwiftypeEngine objects.
 */
interface SwiftypeEngineInterface extends SwiftypeEntityInterface {

  /**
   * Find an engine by its name.
   *
   * @param string $name
   *   Name of engine to find.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface
   *   The found engine.
   *
   * @throws \Drupal\search_api_swiftype\Exception\EngineNotFoundException
   */
  public function findByName($name);

  /**
   * Get the engine name.
   *
   * @return string
   *   The engine name.
   */
  public function getName();

  /**
   * Get the internal identifier of the engine.
   *
   * @return string
   *   The internal identifier of the engine (slug).
   */
  public function getSlug();

  /**
   * Get the internal engine key.
   *
   * @return string
   *   The internal engine key.
   */
  public function getKey();

  /**
   * Get the date the engine has been updated last.
   *
   * @return \DateTimeInterface
   *   Last update date of engine.
   */
  public function getUpdateDate();

  /**
   * Get the number of documents in the engine.
   *
   * @return int
   *   Number of documents in the index.
   */
  public function getDocumentCount();

  /**
   * Get the Url to the engine.
   *
   * @param array $options
   *   (optional) An associative array of additional URL options.
   *   See \Drupal\Core\Url::fromUri() for a list of possible options.
   *
   * @return \Drupal\Core\Url
   *   The engines Url.
   */
  public function getUrl(array $options = []);

  /**
   * Load a single Swiftype engine from the server.
   *
   * @param string $id
   *   The internal identifier of the engine.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface
   *   The loaded engine.
   */
  public function load($id);

  /**
   * Load multiple engines from the server.
   *
   * @param array $ids
   *   List of Ids to load. If empty, all engines are loaded.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface[]
   *   The loaded engines.
   */
  public function loadMultiple(array $ids = []);

  /**
   * Delete a single engine.
   *
   * @throws \Drupal\search_api_swiftype\Exception\SwiftypeException
   */
  public function delete();

}
