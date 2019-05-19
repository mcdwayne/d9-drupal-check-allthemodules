<?php

namespace Drupal\webfactory_master\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Channel source annotation object.
 *
 * Plugin Namespace: Plugin\channel\source.
 *
 * For a working example, see
 * \Drupal\webfactory_master\Plugin\Channel\Source\Bundle.
 *
 * @see \Drupal\webfactory_master\Plugin\Channel\ChannelSourceInterface
 * @see \Drupal\webfactory_master\Plugin\ChannelSourcePluginManager
 * @see \Drupal\webfactory_master\Plugin\Channel\ChannelSourceBase
 * @see plugin_api
 *
 * @Annotation
 */
class ChannelSource extends Plugin {

  /**
   * The resource plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the resource plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
