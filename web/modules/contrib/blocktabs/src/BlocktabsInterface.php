<?php

namespace Drupal\blocktabs;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a blocktabs entity.
 */
interface BlocktabsInterface extends ConfigEntityInterface {

  /**
   * Returns the blocktabs.
   *
   * @return string
   *   The name of the blocktabs.
   */
  public function getName();

  /**
   * Sets the name of the blocktabs.
   *
   * @param string $name
   *   The name of the blocktabs.
   *
   * @return \Drupal\blocktabs\BlocktabsInterface
   *   The class instance this method is called on.
   */
  public function setName($name);

  /**
   * Returns a specific tab.
   *
   * @param string $tab
   *   The tab ID.
   *
   * @return \Drupal\blocktabs\TabInterface
   *   The tab object.
   */
  public function getTab($tab);

  /**
   * Returns the tabs for this blocktabs.
   *
   * @return \Drupal\blocktabs\TabPluginCollection|\Drupal\blocktabs\TabInterface[]
   *   The tab plugin collection.
   */
  public function getTabs();

  /**
   * Saves a tab for this blocktabs.
   *
   * @param array $configuration
   *   An array of tab configuration.
   *
   * @return string
   *   The tab ID.
   */
  public function addTab(array $configuration);

  /**
   * Deletes a tab from this block tabs.
   *
   * @param \Drupal\blocktabs\TabInterface $tab
   *   The tab object.
   *
   * @return $this
   */
  public function deleteTab(TabInterface $tab);

}
