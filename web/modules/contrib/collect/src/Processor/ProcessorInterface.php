<?php
/**
 * @file
 * Contains \Drupal\collect\Processor\ProcessorInterface.
 */

namespace Drupal\collect\Processor;

use Drupal\collect\CollectContainerInterface;
use Drupal\collect\Model\ModelPluginInterface;
use Drupal\collect\TypedData\CollectDataInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\TypedData\ComplexDataInterface;

/**
 * Interface for post-processor plugins.
 */
interface ProcessorInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Returns the plugin label.
   *
   * @return string
   *   The plugin label.
   */
  public function label();

  /**
   * Returns the plugin description.
   *
   * @return string
   *   The plugin description.
   */
  public function getDescription();

  /**
   * Process container data.
   *
   * @param \Drupal\collect\TypedData\CollectDataInterface $data
   *   The typed data of the container to process.
   * @param array $context
   *   The context.
   */
  public function process(CollectDataInterface $data, array &$context);

  /**
   * Returns the model plugin this processor is configured for.
   *
   * @return \Drupal\collect\Model\ModelPluginInterface
   *   The parent model plugin instance.
   */
  public function getModelPlugin();

  /**
   * Set the model plugin this processor is configured for.
   *
   * @param \Drupal\collect\Model\ModelPluginInterface $model_plugin
   *   The parent model plugin instance.
   *
   * @return $this
   */
  public function setModelPlugin(ModelPluginInterface $model_plugin);

  /**
   * Returns a specific item of this plugin's configuration.
   *
   * @param string|array $key
   *   The key of the item to get, or an array of nested keys.
   *
   * @return mixed
   *   An item of this plugin's configuration.
   */
  public function getConfigurationItem($key);

  /**
   * Returns the weight within the model processing workflow.
   *
   * @return int
   *   The current weight.
   */
  public function getWeight();

  /**
   * Sets the weight within the model processing workflow.
   *
   * @param int $weight
   *   The new weight.
   *
   * @return $this
   */
  public function setWeight($weight);

}
