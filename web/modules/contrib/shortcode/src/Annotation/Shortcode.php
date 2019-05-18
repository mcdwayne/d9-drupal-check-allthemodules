<?php

namespace Drupal\shortcode\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an shortcode annotation object.
 *
 * Plugin Namespace: Plugin\Shortcode.
 *
 * For a working example, see
 * \Drupal\shortcode_ecample\Plugin\Shortcode\BootstrapColumnShortcode
 *
 * @see \Drupal\shortcode\ShortcodePluginManager
 * @see \Drupal\shortcode\Plugin\ShortcodeInterface
 * @see \Drupal\shortcode\Plugin\ShortcodeBase
 * @see plugin_api
 *
 * @Annotation
 */
class Shortcode extends Plugin {

  /**
   * The plugin ID.
   *
   * This is used in the backend to identify shortcodes, not used for parsing.
   *
   * @var string
   */
  public $id;

  /**
   * The shortcode token.
   *
   * This is used to parse the shortcode. If not defined, defaults to $id.
   *
   * @var string
   */
  public $token;

  /**
   * The name of the provider that owns the shortcode.
   *
   * @var string
   */
  public $provider;

  /**
   * The human-readable name of the shortcode.
   *
   * This is used as an administrative summary of what the shortcode does.
   *
   * @var \Drupal\Core\Annotation\Translation
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * Additional administrative information about the shortcode's behavior.
   *
   * @var \Drupal\Core\Annotation\Translation
   *  (optional)
   * @ingroup plugin_translatable
   */
  public $description = '';

  /**
   * Whether this shortcode is enabled or disabled by default.
   *
   * @var bool
   *  (optional)
   */
  public $status = TRUE;

  /**
   * Weight of this shortcode.
   *
   * Shortcodes with lower weights are processed
   * first.
   *
   * @var bool
   *  (optional)
   */
  public $weight = 99;

  /**
   * The default settings for the shortcode.
   *
   * @var array
   *  (optional)
   */
  public $settings = [];

}
