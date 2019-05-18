<?php

/**
 * @file
 * Contains \Drupal\block_page\Plugin\PageVariantInterface.
 */

namespace Drupal\block_page\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface for PageVariant plugins.
 */
interface PageVariantInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Returns the user-facing page variant label.
   *
   * @return string
   *   The page variant label.
   */
  public function label();

  /**
   * Returns the admin-facing page variant label.
   *
   * This is for the type of page variant, not the configured variant itself.
   *
   * @return string
   *   The page variant administrative label.
   */
  public function adminLabel();

  /**
   * Returns the unique ID for the page variant.
   *
   * @return string
   *   The page variant ID.
   */
  public function id();

  /**
   * Returns the weight of the page variant.
   *
   * @return int
   *   The page variant weight.
   */
  public function getWeight();

  /**
   * Sets the weight of the page variant.
   *
   * @param int $weight
   *   The weight to set.
   */
  public function setWeight($weight);

  /**
   * Returns a specific block plugin.
   *
   * @param string $block_id
   *   The block ID.
   *
   * @return \Drupal\block\BlockPluginInterface
   *   The block plugin.
   */
  public function getBlock($block_id);

  /**
   * Adds a block to this page variant.
   *
   * @param array $configuration
   *   An array of block configuration.
   *
   * @return string
   *   The block ID.
   */
  public function addBlock(array $configuration);

  /**
   * Updates the configuration of a specific block plugin.
   *
   * @param string $block_id
   *   The block ID.
   * @param array $configuration
   *   The array of configuration to set.
   *
   * @return $this
   */
  public function updateBlock($block_id, array $configuration);

  /**
   * Returns the region a specific block is assigned to.
   *
   * @param string $block_id
   *   The block ID.
   *
   * @return string
   *   The machine name of the region this block is assigned to.
   */
  public function getRegionAssignment($block_id);

  /**
   * Returns an array of regions and their block plugins.
   *
   * @return array
   *   The array is first keyed by region machine name, with the values
   *   containing an array keyed by block ID, with block plugin instances as the
   *   values.
   */
  public function getRegionAssignments();

  /**
   * Returns the human-readable list of regions keyed by machine name.
   *
   * @return array
   *   An array of human-readable region names keyed by machine name.
   */
  public function getRegionNames();

  /**
   * Returns the human-readable name of a specific region.
   *
   * @param string $region
   *   The machine name of a region.
   *
   * @return string
   *   The human-readable name of a region.
   */
  public function getRegionName($region);

  /**
   * Returns the number of blocks contained by the page variant.
   *
   * @return int
   *   The number of blocks contained by the page variant.
   */
  public function getBlockCount();

  /**
   * Determines if this page variant is accessible.
   *
   * @return bool
   *   TRUE if this page variant is accessible, FALSE otherwise.
   */
  public function access();

  /**
   * Returns the render array for the page variant.
   *
   * @return array
   *   A render array for the page variant.
   */
  public function render();

  /**
   * Returns the conditions used for determining if this page variant is selected.
   *
   * @return \Drupal\Core\Condition\ConditionInterface[]|\Drupal\block_page\Plugin\ConditionPluginBag
   *   An array of configured condition plugins.
   */
  public function getSelectionConditions();

  /**
   * Adds a new selection condition to the block page.
   *
   * @param array $configuration
   *   An array of configuration for the new selection condition.
   *
   * @return string
   *   The selection condition ID.
   */
  public function addSelectionCondition(array $configuration);

  /**
   * Retrieves a specific selection condition.
   *
   * @param string $condition_id
   *   The selection condition ID.
   *
   * @return \Drupal\Core\Condition\ConditionInterface
   *   The selection condition object.
   */
  public function getSelectionCondition($condition_id);

  /**
   * Removes a specific selection condition.
   *
   * @param string $condition_id
   *   The selection condition ID.
   *
   * @return $this
   */
  public function removeSelectionCondition($condition_id);

  /**
   * Returns the logic used to compute selections, either 'and' or 'or'.
   *
   * @return string
   *   The string 'and', or the string 'or'.
   */
  public function getSelectionLogic();

  /**
   * Gets the values for all defined contexts.
   *
   * @return \Drupal\Component\Plugin\Context\ContextInterface[]
   *   An array of set contexts, keyed by context name.
   */
  public function getContexts();

  /**
   * Sets the context values for this page variant.
   *
   * @param \Drupal\Component\Plugin\Context\ContextInterface[] $contexts
   *   An array of contexts, keyed by context name.
   *
   * @return $this
   */
  public function setContexts(array $contexts);

}
