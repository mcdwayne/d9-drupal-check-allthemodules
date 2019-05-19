<?php

/**
 * @file
 * Token hooks for the uc_fulfillment module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\uc_fulfillment\Shipment;

/**
 * Implements hook_token_info().
 */
function uc_fulfillment_token_info() {
  $tokens = [];

  $tokens['tracking-number'] = [
    'name' => t('Shipment tracking number(s)'),
    'description' => t('Tracking number(s) (if applicable) for product shipments.'),
  ];

  return [
    'tokens' => ['uc_order' => $tokens],
  ];
}

/**
 * Implements hook_tokens().
 */
function uc_fulfillment_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'uc_order' && !empty($data['uc_order'])) {
    $order = $data['uc_order'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'tracking-number':
          $tracking_numbers = [];
          $shipments = Shipment::loadByOrder($order->id());
          foreach ($shipments as $shipment) {
            if ($shipment->getTrackingNumber()) {
              $tracking_numbers[] = $shipment->getTrackingNumber();
            }
          }
          $replacements[$original] = implode(', ', $tracking_numbers);
          break;
      }
    }
  }

  return $replacements;
}
