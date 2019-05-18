<?php

namespace Drupal\blockchain\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a BlockchainAuth item annotation object.
 *
 * @see \Drupal\blockchain\Plugin\BlockchainAuthManager
 *
 * @Annotation
 */
class BlockchainAuth extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
