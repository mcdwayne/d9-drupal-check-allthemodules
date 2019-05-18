<?php
/**
 * @file
 * Contains \Drupal\collect\ModelManagerInterface.
 */

namespace Drupal\collect\Model;

use Drupal\collect\CollectContainerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines methods for the Collect model plugin manager.
 */
interface ModelManagerInterface extends PluginManagerInterface {

  /**
   * Returns an instance of a plugin based on schema URI matching.
   *
   * @param string $uri
   *   A schema URI.
   *
   * @return \Drupal\collect\Model\ModelPluginInterface
   *   A matching model plugin. If no matching plugin is found, the default
   *   \Drupal\collect\Plugin\collect\Model\DefaultModelPlugin plugin is returned.
   */
  public function createInstanceFromUri($uri);

  /**
   * Returns an instance of a plugin based on a model entity.
   *
   * @param \Drupal\collect\Model\ModelInterface $model
   *   A model entity.
   *
   * @return \Drupal\collect\Model\ModelPluginInterface
   *   A matching model plugin. If no matching plugin is found, the default
   *   \Drupal\collect\Plugin\collect\Model\DefaultModelPlugin plugin is returned.
   */
  public function createInstanceFromConfig(ModelInterface $model);

  /**
   * Loads a model matching the given URI.
   *
   * If multiple models are matching, the one with the most specific
   * pattern is returned.
   *
   * @param string $uri
   *   A schema URI.
   *
   * @return \Drupal\collect\Model\ModelInterface|null
   *   A model entity, or NULL if there is no match.
   */
  public function loadModelByUri($uri);

  /**
   * Suggests a new model for a container.
   *
   * This finds a plugin declared to support the container schema URI and
   * creates (without saving) a model with appropriate defaults.
   *
   * @param \Drupal\collect\CollectContainerInterface $container
   *   A container to suggest model from.
   *
   * @return \Drupal\collect\Model\ModelInterface|null
   *   The new suggested model, or NULL if no supporting model is
   *   found.
   */
  public function suggestModel(CollectContainerInterface $container);

  /**
   * Get property definitions suggested by a model plugin.
   *
   * If the model is dynamic, this inspects stored data in order to add extra
   * properties.
   *
   * @param \Drupal\collect\Model\ModelInterface $model
   *   A model for the plugin.
   *
   * @return \Drupal\collect\Model\PropertyDefinition[]
   *   A list of property definitions.
   */
  public function suggestProperties(ModelInterface $model);

  /**
   * Loads a container whose schema URI matches a given pattern.
   *
   * @param string $uri_pattern
   *   Schema URI pattern.
   *
   * @return \Drupal\collect\CollectContainerInterface|null
   *   The collect container whose schema URI matches the pattern, or NULL if
   *   no such container was found.
   */
  public function loadContainer($uri_pattern);

  /**
   * Returns whether given container model is revisionable.
   *
   * @param \Drupal\collect\CollectContainerInterface $container
   *   The collect container.
   *
   * @return bool
   *   TRUE if the container model is revisionable, FALSE otherwise.
   */
  public function isModelRevisionable(CollectContainerInterface $container);

}
