<?php

namespace Drupal\blockchain\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a BlockchainData item annotation object.
 *
 * @see \Drupal\blockchain\Plugin\BlockchainDataManager
 *
 * @Annotation
 */
class BlockchainData extends Plugin {

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

  /**
   * Target class.
   *
   * @var string
   */
  public $targetClass;

}
