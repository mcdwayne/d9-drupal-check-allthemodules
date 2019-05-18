<?php

namespace Drupal\akamai\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for the Akamai client plugin.
 *
 * An Akamai client provides a specific version of the client CCU.
 *
 * Plugin namespace: Plugin\Client
 *
 * For a working example, see
 * \Drupal\akamai\Plugin\Client\AkamaiClientV2
 *
 * @see plugin_api
 *
 * @Annotation
 */
class AkamaiClient extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the Akamai client.
   *
   * The string should be wrapped in a @Translation().
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

}
