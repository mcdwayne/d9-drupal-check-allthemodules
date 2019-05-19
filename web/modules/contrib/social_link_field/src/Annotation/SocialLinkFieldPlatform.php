<?php

namespace Drupal\social_link_field\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a platform item annotation object.
 *
 * @Annotation
 */
class SocialLinkFieldPlatform extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * UTF code of the icon.
   *
   * @var string
   */
  public $icon;

  /**
   * UTF code of the square icon.
   *
   * @var string
   */
  public $iconSquare;

  /**
   * The name of the platform.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * The url prefix of the platform.
   *
   * @var string
   */
  public $urlPrefix;

  /**
   * The url suffix of the platform.
   *
   * @var string
   */
  public $urlSuffix;

}
