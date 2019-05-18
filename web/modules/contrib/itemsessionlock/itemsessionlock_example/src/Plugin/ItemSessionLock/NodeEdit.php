<?php

/**
 * @file
 * Contains \Drupal\itemsessionlock\Plugin\ItemSessionLock\NodeEdit.
 */

namespace Drupal\itemsessionlock_example\Plugin\ItemSessionLock;

use Drupal\itemsessionlock\Plugin\ItemSessionLock\ItemSessionLockBase;

/**
 * Provides a 'Node edit' lock.
 *
 * @ItemSessionLock(
 *   id = "itemsessionlock_example_node_edit",
 *   label = @Translation("Node")
 * )
 */
class NodeEdit extends ItemSessionLockBase {

}