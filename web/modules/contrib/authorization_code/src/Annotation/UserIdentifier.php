<?php

namespace Drupal\authorization_code\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Plugin Namespace: Plugin\UserIdentifier.
 *
 * For a working example, see
 * \Drupal\authorization_code\Plugin\UserIdentifier\UserId.
 *
 * @see \Drupal\authorization_code\UserIdentifierInterface
 * @see plugin_api
 *
 * @Annotation
 */
class UserIdentifier extends Plugin {

  /**
   * The plugin id.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin title.
   *
   * @var string
   */
  public $title;

}
