<?php

namespace Drupal\wordfilter\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\wordfilter\WordfilterItem;

/**
 * Provides an interface for defining Wordfilter configuration entities.
 */
interface WordfilterConfigurationInterface extends ConfigEntityInterface {
  /**
   * Get the assigned filtering process.
   * 
   * @return \Drupal\wordfilter\Plugin\WordfilterProcessInterface
   *   The assigned filtering process.
   */
  public function getProcess();
  
  /**
   * Assign the filtering process.
   * 
   * @param \Drupal\wordfilter\Plugin\WordfilterProcessInterface $process
   *   The Wordfilter process to assign. 
   */
  public function setProcess(\Drupal\wordfilter\Plugin\WordfilterProcessInterface $process);

  /**
   * Get the filtering items.
   *
   * @return \Drupal\wordfilter\WordfilterItem[]
   *  An array of filtering items, keyed and sorted by delta.
   */
  public function getItems();

  /**
   * Creates a new item, which is part of this configuration.
   *
   * You may also use this function to "reset" a given item to empty values.
   *
   * @param integer $delta
   *   (Optional) The delta for the new item.
   *
   * @return \Drupal\wordfilter\WordfilterItem
   */
  public function newItem($delta = NULL);

  /**
   * Remove the filtering item from this configuration.
   *
   * The item must belong to this configuration,
   * otherwise it won't be removed.
   *
   * @param \Drupal\wordfilter\WordfilterItem $item
   * @return bool
   *   TRUE if removal was successful, FALSE otherwise.
   */
  public function removeItem(WordfilterItem $item);
}
