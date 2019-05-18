<?php

namespace Drupal\aes\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an encryption/decryption implementation.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class Cryptor extends Plugin {

  /**
   * The ID of plugin.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var string
   */
  public $label;

  /**
   * The description of the plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var string
   */
  public $description;

}
