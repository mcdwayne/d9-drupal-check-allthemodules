<?php

namespace Drupal\panels_extended_blocks\BlockConfig;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\panels_extended\BlockConfig\BlockConfigBase;
use Drupal\panels_extended_blocks\NodeListBlockBase;

/**
 * Block configuration for preventing duplicate nodes with the same page.
 */
class PreventNodeDuplication extends BlockConfigBase implements AlterBlockDataInterface, AlterQueryInterface {

  const PANELS_EXTENDED_BLOCKS_USED_NODES = 'pe_blocks_used_nodes';

  /**
   * Should the block exclude already used nodes?
   *
   * @var bool
   */
  protected $preventUsedNodes;

  /**
   * Should the nodes of this block be added to the used nodes list?
   *
   * @var bool
   */
  protected $addToUsedNodes;

  /**
   * Constructor.
   *
   * @param \Drupal\panels_extended_blocks\NodeListBlockBase $block
   *   The block.
   * @param bool $preventUsedNodes
   *   TRUE to prevent node IDs from the used nodes list in this block.
   * @param bool $addToUsedNodes
   *   TRUE to add the found nodes to the used nodes list.
   */
  public function __construct(NodeListBlockBase $block, $preventUsedNodes = TRUE, $addToUsedNodes = TRUE) {
    parent::__construct($block);

    $this->preventUsedNodes = $preventUsedNodes;
    $this->addToUsedNodes = $addToUsedNodes;
  }

  /**
   * {@inheritdoc}
   */
  public function alterQuery(SelectInterface $query, $isCountQuery) {
    if ($this->preventUsedNodes) {
      $nids = self::getUsedNodes();
      if (empty($nids)) {
        return;
      }
      $query->condition('nfd.nid', $nids, 'NOT IN');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterBlockData(array &$nids) {
    if ($this->addToUsedNodes) {
      self::addToUsedNodes($nids);
    }
  }

  /**
   * Gets a list of already used nodes for the current page request.
   *
   * @return int[]
   *   A list of node IDs.
   */
  public static function getUsedNodes() {
    static $drupal_static_fast;
    if (!isset($drupal_static_fast)) {
      $drupal_static_fast[self::PANELS_EXTENDED_BLOCKS_USED_NODES] = &drupal_static(self::PANELS_EXTENDED_BLOCKS_USED_NODES);
    }
    $usedNodes = &$drupal_static_fast[self::PANELS_EXTENDED_BLOCKS_USED_NODES];
    if ($usedNodes === NULL) {
      $usedNodes = [];
    }
    return $usedNodes;
  }

  /**
   * Adds the node IDs to the used nodes list for the current page request.
   *
   * @param array $nids
   *   The node IDs to add.
   */
  public static function addToUsedNodes(array $nids) {
    if (empty($nids)) {
      return;
    }

    static $drupal_static_fast;
    if (!isset($drupal_static_fast)) {
      $drupal_static_fast[self::PANELS_EXTENDED_BLOCKS_USED_NODES] = &drupal_static(self::PANELS_EXTENDED_BLOCKS_USED_NODES);
    }

    $usedNodes = &$drupal_static_fast[self::PANELS_EXTENDED_BLOCKS_USED_NODES];
    if ($usedNodes === NULL) {
      print 'leeg...';
      $usedNodes = [];
    }

    foreach ($nids as $nid) {
      if (is_numeric($nid)) {
        $usedNodes[] = (int) $nid;
      }
    }
    $usedNodes = array_unique($usedNodes);
  }

}
