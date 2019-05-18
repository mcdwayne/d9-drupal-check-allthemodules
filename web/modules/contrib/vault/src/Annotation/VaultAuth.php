<?php

namespace Drupal\vault\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Vault Authentication item annotation object.
 *
 * @see \Drupal\vault\Plugin\VaultAuthManager
 * @see plugin_api
 *
 * @Annotation
 */
class VaultAuth extends Plugin {


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
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
