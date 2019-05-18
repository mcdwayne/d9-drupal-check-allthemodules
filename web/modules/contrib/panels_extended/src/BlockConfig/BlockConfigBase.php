<?php

namespace Drupal\panels_extended\BlockConfig;

use Drupal\panels_extended\JsonBlockBase;

/**
 * Provides a base class for block configurations.
 */
abstract class BlockConfigBase {

  /**
   * The block.
   *
   * @var \Drupal\panels_extended\JsonBlockBase
   */
  protected $block;

  /**
   * The current block configuration.
   *
   * @var array
   */
  protected $configuration;

  /**
   * Constructor.
   *
   * @param \Drupal\panels_extended\JsonBlockBase $block
   *   The block.
   */
  public function __construct(JsonBlockBase $block) {
    $this->block = $block;
    $this->configuration = $block->getConfiguration();
  }

}
