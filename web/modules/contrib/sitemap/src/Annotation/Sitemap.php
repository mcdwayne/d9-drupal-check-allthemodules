<?php

namespace Drupal\sitemap\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Sitemap annotation object.
 *
 * @Annotation
 *
 * @see \Drupal\sitemap\SitemapManager
 * @see \Drupal\sitemap\SitemapInterface
 *
 * @ingroup sitemap
 */
class Sitemap extends Plugin {

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
   * A short description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * An integer to determine the weight of this item relative to other items in
   * the Sitemap display.
   *
   * @var int optional
   */
  public $weight = NULL;

  /**
   * Whether this plugin is enabled or disabled by default.
   *
   * @var bool optional
   */
  public $enabled = FALSE;

  /**
   * The default settings for the plugin.
   *
   * @var array optional
   */
  public $settings = [];

}
