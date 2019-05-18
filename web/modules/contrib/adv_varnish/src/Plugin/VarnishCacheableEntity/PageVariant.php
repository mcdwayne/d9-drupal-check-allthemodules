<?php

/**
 * @file
 * Contains \Drupal\adv_varnish\Plugin\VarnishCacheableEntity\PageVariant.
 */

namespace Drupal\adv_varnish\Plugin\VarnishCacheableEntity;

use Drupal\adv_varnish\VarnishCacheableEntityBase;

/**
 * Provides a language config pages context.
 *
 * @VarnishCacheableEntity(
 *   id = "page_variant",
 *   label = @Translation("Page"),
 *   entity_type = "page_variant",
 *   per_bundle_settings = 0
 * )
 */
class PageVariant extends VarnishCacheableEntityBase {

  protected $displayVariant;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if ($this->entity instanceof \Drupal\page_manager\Entity\PageVariant) {
      if (empty($configuration['options']['displayVariant'])) {
        $display_variant = $this->entity->id();
      }
      else {
        $display_variant = $configuration['options']['displayVariant'];
      }
      $this->displayVariant = $display_variant;
    }
  }

  /**
   * Generate a entity cache key.
   */
  public function generateSettingsKey() {
    $display_variant = $this->displayVariant ?: '';
    $page = $this->entity;
    $type = $page->getEntityTypeId();
    $id = $page->id();

    return $display_variant ? 'entities_settings.' . $type . '.' . $id . '.' . $display_variant : '';
  }

}
