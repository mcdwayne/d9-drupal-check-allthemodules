<?php

/**
 * @file
 * Advertising Entity: DFP implementations for Accelerated Mobile Pages (AMP).
 */

use Drupal\Core\Template\Attribute;
use Drupal\Component\Serialization\Json;
use Drupal\ad_entity_dfp\Plugin\ad_entity\AdView\DFPAmp;

/**
 * Preprocess implementation for a DFP tag for AMP.
 *
 * @param array &$variables
 *   An array of available variables.
 */
function template_preprocess_dfp_amp(array &$variables) {
  /** @var \Drupal\ad_entity\Entity\AdEntityInterface $ad_entity */
  $ad_entity = $variables['ad_entity'];
  $settings = $ad_entity->getThirdPartySettings('ad_entity_dfp');

  $slot_id = '/' . $settings['network_id'] . '/' . $settings['unit_id'];

  // Generate attributes.
  $attributes = new Attribute(['type' => 'doubleclick']);
  $attributes->setAttribute('data-slot', $slot_id);
  $attributes->setAttribute('width', $settings['amp']['width']);
  $attributes->setAttribute('height', $settings['amp']['height']);
  if (!empty($settings['sizes'])) {
    $sizes = [];
    $decoded = Json::decode($settings['sizes']);
    foreach ($decoded as $size) {
      // Currently, fluid is not a valid size here.
      // Validation would say: Invalid multi-size data format 'fluid'.
      // @todo Find a proper way to support native ads on AMP pages.
      if (is_array($size)) {
        $sizes[] = $size[0] . 'x' . $size[1];
      }
    }
    $sizes = implode(',', $sizes);
    $attributes->setAttribute('data-multi-size', $sizes);
  }
  if (isset($settings['amp']['multi_size_validation'])) {
    if ($settings['amp']['multi_size_validation']) {
      $attributes->setAttribute('data-multi-size-validation', 'true');
    }
    else {
      $attributes->setAttribute('data-multi-size-validation', 'false');
    }
  }
  if (!empty($settings['amp']['consent']['block_behavior'])) {
    $block_behavior = $settings['amp']['consent']['block_behavior'];
    if (!($block_behavior == '0')) {
      $allowed_options = DFPAmp::blockOnConsentOptions();
      if (isset($allowed_options[$block_behavior])) {
        $attributes->setAttribute('data-block-on-consent', $block_behavior);
      }
    }
  }
  if (!empty($settings['amp']['consent']['npa_unknown'])) {
    $attributes->setAttribute('data-npa-on-unknown-consent', 'true');
  }
  $variables['attributes'] = $attributes;

  // Generate the json attribute.
  $json = [];
  if (!empty($settings['amp']['same_domain_rendering'])) {
    $json['useSameDomainRenderingUntilDeprecated'] = 1;
  }
  // Insert targeting from backend context data.
  $targeting_collection = $ad_entity->getTargetingFromContextData();
  if (!$targeting_collection->isEmpty()) {
    // Filter the targeting information before being displayed.
    $targeting_collection->filter();
    $json['targeting'] = $targeting_collection->toArray();
  }
  $variables['json'] = $json;

  // Generate the rtc_config attribute.
  $rtc_config = [];
  if (isset($settings['amp']['rtc_config']['vendors'])) {
    foreach ($settings['amp']['rtc_config']['vendors']['vendor_items'] as $vendor) {
      $vendor_values = [];
      $values = explode(",", $vendor['vendor_values']);
      foreach ($values as $value) {
        $tmp = explode(':', $value);
        $vendor_values[$tmp[0]] = $tmp[1];
      }
      $rtc_config['vendors'][$vendor['vendor']] = $vendor_values;
    }
    if (!empty($settings['amp']['rtc_config']['urls'])) {
      $rtc_config['urls'] = [$settings['amp']['rtc_config']['urls']];
    }
    if (!empty($settings['amp']['rtc_config']['timeoutMillis'])) {
      $rtc_config['timeoutMillis'] = $settings['amp']['rtc_config']['timeoutMillis'];
    }
    $variables['rtc_config'] = $rtc_config;
  }

}
