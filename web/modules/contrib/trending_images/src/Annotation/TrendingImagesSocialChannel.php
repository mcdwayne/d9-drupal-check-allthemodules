<?php

namespace Drupal\trending_images\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a TrendingSocialChannel annotation object.
 *
 * TrendingSocialChannel handle the fetching requests from social networks.
 * They are typically instantiated and invoked by an EntityDisplay object.
 *
 * Additional annotation keys for formatters can be defined in
 * hook_trending_social_channel_info_alter().
 *
 * @Annotation
 *
 * @see \Drupal\trending_images\TrendingImagesManager
 *
 * @ingroup trending_images_social_channel
 */
class TrendingImagesSocialChannel extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * Channel which this plugin represents.
   *
   * @var string
   * */
  public $channel;

  /**
   * The human-readable name of the trending_social_channel type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
