<?php

/**
 * @file
 * Post update functions for Panels Everywhere.
 */

/**
 * Upgrade path for existing sites, adds route override to every variant.
 */
function panels_everywhere_post_update_route_override() {
  $entity_type_manager = \Drupal::entityTypeManager();
  $entities = $entity_type_manager->getStorage('page')->loadMultiple();
  /* @var $entity \Drupal\page_manager\Entity\Page */
  foreach ($entities as $entity) {
    $route_override = $entity->getThirdPartySetting('panels_everywhere', 'disable_route_override');
    $variants = $entity->getVariants();
    /* @var $variant \Drupal\page_manager\Entity\PageVariant */
    foreach ($variants as $variant) {
      $settings = $variant->get('variant_settings');
      $settings['route_override_enabled'] = $route_override;
      $variant->set('variant_settings', $settings);
      $variant->save();
    }
    $entity->unsetThirdPartySetting('panels_everywhere', 'disable_route_override');
    $entity->save();
  }
  \Drupal::service('router.builder')->rebuild();
}
