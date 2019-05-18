<?php
/**
 * @file
 * Contains \Drupal\collect\Model\ModelPluginInterface.
 */

namespace Drupal\collect\Model;

use Drupal\collect\CollectContainerInterface;
use Drupal\collect\TypedData\CollectDataInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines methods for model plugins.
 */
interface ModelPluginInterface extends PluginInspectionInterface {

  /**
   * Returns the configuration for the plugin.
   *
   * @return \Drupal\collect\Model\ModelInterface
   *   The model, if one was passed to the constructor, otherwise NULL.
   */
  public function getConfig();

  /**
   * Returns the label of the model plugin.
   *
   * @return string
   *   Model label.
   */
  public function getLabel();

  /**
   * Returns the description of the model plugin.
   *
   * @return string
   *   Description text.
   */
  public function getDescription();

  /**
   * Returns a helpful description of the model plugin.
   *
   * @return string|array
   *   A string or a renderable array that describes the features and purpose of
   *   the model plugin.
   */
  public function help();

  /**
   * Returns the URI patterns suggested to work with this plugin.
   *
   * @return string[]
   *   A list of known compatible schema URI patterns.
   */
  public function getPatterns();

  /**
   * Suggests a new model for a container.
   *
   * This creates (without saving) a model with appropriate defaults.
   *
   * @param \Drupal\collect\CollectContainerInterface $container
   *   A container to suggest model from.
   * @param array $plugin_definition
   *   The plugin definition corresponding to the annotation on the plugin
   *   class.
   *
   * @return \Drupal\collect\Model\ModelInterface|null
   *   The new suggested model, or NULL if no suggestion can be made.
   */
  public static function suggestConfig(CollectContainerInterface $container, array $plugin_definition);

  /**
   * Extract and cast raw data to a type accepted by the build methods.
   *
   * @param \Drupal\collect\CollectContainerInterface $collect_container
   *   Collect container object which use this model.
   *
   * @return mixed
   *   Data, processed and casted as needed.
   *
   * @see \Drupal\collect\Model\ModelPluginInterface::build()
   * @see \Drupal\collect\Model\ModelPluginInterface::buildTeaser()
   */
  public function parse(CollectContainerInterface $collect_container);

  /**
   * Build a renderable array for a limited view of the data of a Container.
   *
   * @todo Implement view modes, https://www.drupal.org/node/2420839
   *
   * @param \Drupal\collect\TypedData\CollectDataInterface $data
   *   Typed data of this plugin.
   *
   * @return array
   *   A renderable array representing main parts of the content.
   */
  public function buildTeaser(CollectDataInterface $data);

  /**
   * Returns an interface to the typed data definitions of the model.
   *
   * @return \Drupal\collect\Model\ModelTypedDataInterface
   *   The typed data definition provider.
   */
  public function getTypedData();

  /**
   * Returns a query evaluator for the model plugin.
   *
   * @return \Drupal\collect\Query\QueryEvaluatorInterface
   *   The query evaluator.
   */
  public function getQueryEvaluator();

}
