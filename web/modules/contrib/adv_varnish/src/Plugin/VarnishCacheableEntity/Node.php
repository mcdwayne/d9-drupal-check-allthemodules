<?php

/**
 * @file
 * Contains \Drupal\adv_varnish\Plugin\VarnishCacheableEntity\Page.
 */

namespace Drupal\adv_varnish\Plugin\VarnishCacheableEntity;

use Drupal\adv_varnish\VarnishCacheableEntityBase;

/**
 * Provides a language config pages context.
 *
 * @VarnishCacheableEntity(
 *   id = "node",
 *   label = @Translation("Node"),
 *   entity_type = "node",
 *   per_bundle_settings = 1
 * )
 */
class Node extends VarnishCacheableEntityBase {

}
