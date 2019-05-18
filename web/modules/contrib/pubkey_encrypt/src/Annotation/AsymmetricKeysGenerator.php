<?php

namespace Drupal\pubkey_encrypt\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Asymmetric Keys Generator annotation object.
 *
 * @see \Drupal\pubkey_encrypt\Plugin\AsymmetricKeysManager
 * @see plugin_api
 *
 * @Annotation
 */
class AsymmetricKeysGenerator extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
