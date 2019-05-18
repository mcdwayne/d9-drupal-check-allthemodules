<?php

namespace Drupal\pubkey_encrypt\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Login Credentials Provider annotation object.
 *
 * @see \Drupal\pubkey_encrypt\Plugin\LoginCredentialsManager
 * @see plugin_api
 *
 * @Annotation
 */
class LoginCredentialsProvider extends Plugin {

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
