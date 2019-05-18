<?php

/**
 * @file
 * Advertising Entity: DFP iFrame implementations.
 */

use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\Crypt;

/**
 * Preprocess implementation for a DFP tag as iFrame.
 *
 * @param array &$variables
 *   An array of available variables.
 */
function template_preprocess_dfp_iframe(array &$variables) {
  /** @var \Drupal\ad_entity\Entity\AdEntityInterface $ad_entity */
  $ad_entity = $variables['ad_entity'];
  $settings = $ad_entity->getThirdPartySettings('ad_entity_dfp');

  // Insert the base library into the iframe, for being able
  // to include custom configurations and consent awareness.
  // Therefore, load its JS library files and embed them inline.
  $variables['ad_entity_base'] = _ad_entity_js_base_inline();

  // Generate attributes.
  $id = 'google-dfp-' . Crypt::randomBytesBase64(8);
  $attributes = new Attribute(['id' => $id]);
  $attributes->addClass('google-dfp-ad');
  $attributes->addClass('dfp-iframe-view');
  $variables['attributes'] = $attributes;

  $variables['out_of_page'] = !empty($settings['out_of_page']);

  $variables['slot_id'] = '/' . $settings['network_id'] . '/' . $settings['unit_id'];

  // Insert targeting from backend context data.
  $targeting_collection = $ad_entity->getTargetingFromContextData();
  if (!$targeting_collection->isEmpty()) {
    // Filter the targeting information before being displayed.
    $targeting_collection->filter();
    $variables['targeting'] = $targeting_collection->toJson();
  }
  else {
    $variables['targeting'] = '{}';
  }

  if (!empty($settings['sizes'])) {
    $variables['sizes'] = $settings['sizes'];
  }
  if (!empty($settings['iframe'])) {
    $variables['iframe'] = $settings['iframe'];
  }
}
