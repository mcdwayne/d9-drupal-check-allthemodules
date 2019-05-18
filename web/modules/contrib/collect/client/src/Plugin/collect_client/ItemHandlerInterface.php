<?php
/**
 * @file
 * Contains \Drupal\collect_client\Plugin\collect_client\ItemHandlerInterface.
 */

namespace Drupal\collect_client\Plugin\collect_client;

interface ItemHandlerInterface {

  /**
   * Checks if the item is supported by this plugin.
   *
   * @param mixed $item
   *   The item to check support.
   *
   * @return bool
   *   TRUE if the item is supported, FALSE otherwise.
   */
  public function supports($item);

  /**
   * Handles the item.
   *
   * @param mixed $item
   *   The item to handle.
   *
   * @return \Drupal\collect_client\CollectItem
   *   The data transfer object.
   */
  public function handle($item);

}
