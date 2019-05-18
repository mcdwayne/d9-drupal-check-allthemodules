<?php

namespace Drupal\gtm_datalayer\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Provides an interface defining a gtm_datalayer entity.
 */
interface DataLayerInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Sets the label for the GTM dataLayer.
   *
   * @param string $label
   *   The label for the GTM dataLayer.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Returns the description for the GTM dataLayer.
   *
   * @return string
   *   The description for the GTM dataLayer.
   */
  public function getDescription();

  /**
   * Sets the description for the GTM dataLayer.
   *
   * @param string $description
   *   The description for the GTM dataLayer.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Returns the processor plugin ID for the GTM dataLayer.
   *
   * @return string
   *   The GTM dataLayer Processor plugin ID for this GTM dataLayer.
   */
  public function getPlugin();

  /**
   * Sets the processor plugin ID for the GTM dataLayer.
   *
   * @param string $plugin_id
   *   The GTM dataLayer Processor plugin ID for this GTM dataLayer.
   *
   * @return $this
   */
  public function setPlugin($plugin_id);

  /**
   * Returns the weight for the GTM dataLayer (used for sorting).
   *
   * @return int
   *   The GTM dataLayer weight.
   */
  public function getWeight();

  /**
   * Sets the weight for the GTM dataLayer.
   *
   * @param int $weight
   *   The desired weight.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Returns the conditions used for determining access for this GTM dataLayer.
   *
   * @return \Drupal\Core\Condition\ConditionInterface[]|\Drupal\Core\Condition\ConditionPluginCollection
   *   An array of configured condition plugins.
   */
  public function getAccessConditions();

  /**
   * Adds a new access condition to the GTM dataLayer.
   *
   * @param array $configuration
   *   An array of configuration for the new GTM dataLayer.
   *
   * @return string
   *   The GTM dataLayer ID.
   */
  public function addAccessCondition(array $configuration);

  /**
   * Retrieves a specific access condition.
   *
   * @param string $condition_id
   *   The GTM dataLayer ID.
   *
   * @return \Drupal\Core\Condition\ConditionInterface
   *   The GTM dataLayer object.
   */
  public function getAccessCondition($condition_id);

  /**
   * Removes a specific access condition.
   *
   * @param string $condition_id
   *   The GTM dataLayer ID.
   *
   * @return $this
   */
  public function removeAccessCondition($condition_id);

  /**
   * Returns the logic used to compute access, either 'and' or 'or'.
   *
   * @return string
   *   The string 'and', or the string 'or'.
   */
  public function getAccessLogic();

  /**
   * Sets the logic used to compute access, either 'and' or 'or'.
   *
   * @param string $access_logic
   *   The access logic string.
   *
   * @return $this
   */
  public function setAccessLogic($access_logic);

  /**
   * Returns the dataLayer Processor instance.
   *
   * @return \Drupal\gtm_datalayer\Plugin\DataLayerProcessorInterface
   *   The GTM dataLayer Processor plugin instance for this GTM dataLayer.
   */
  public function getDataLayerProcessor();

}
