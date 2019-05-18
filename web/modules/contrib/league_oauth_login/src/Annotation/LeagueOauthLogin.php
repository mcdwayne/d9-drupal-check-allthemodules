<?php

namespace Drupal\league_oauth_login\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines league_oauth_login annotation object.
 *
 * @Annotation
 */
class LeagueOauthLogin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * Whether or not one can log in with this provider plugin.
   *
   * @var bool
   */
  // @codingStandardsIgnoreLine
  public $login_enabled;

}
