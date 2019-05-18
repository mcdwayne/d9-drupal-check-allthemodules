<?php

/**
 * @file
 * Advertising Entity: Smart theme implementations.
 */

use Drupal\Core\Template\Attribute;

/**
 * Preprocess implementation for a default Smart tag view.
 *
 * @param array &$variables
 *   An array of available variables.
 */
function template_preprocess_smart_default(array &$variables) {
  /** @var \Drupal\ad_entity\Entity\AdEntityInterface $ad_entity */
  $ad_entity = $variables['ad_entity'];
  $settings = $ad_entity->getThirdPartySettings('ad_entity_smart');

  // Generate attributes.
  $attributes = new Attribute(['id' => 'sas_' . $settings['format_id']]);
  $attributes->addClass('smart-ad');
  $attributes->addClass('smart-default-view');
  $attributes->addClass('smart-ad--' . $ad_entity->id());
  $attributes->setAttribute('data-ad-id', $settings['format_id']);
  $attributes->setAttribute('data-ad-type', $ad_entity->id());
  $variables['attributes'] = $attributes;

  // Insert targeting from backend context data.
  $targeting_collection = $ad_entity->getTargetingFromContextData();
  $variables['targeting'] = $targeting_collection;
}
