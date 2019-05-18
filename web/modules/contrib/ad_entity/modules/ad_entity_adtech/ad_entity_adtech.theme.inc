<?php

/**
 * @file
 * Advertising Entity: AdTech Factory theme implementations.
 */

use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\Crypt;
use Drupal\ad_entity\TargetingCollection;

/**
 * Preprocess implementation for a default AdTech Factory tag view.
 *
 * @param array &$variables
 *   An array of available variables.
 */
function template_preprocess_adtech_default(array &$variables) {
  /** @var \Drupal\ad_entity\Entity\AdEntityInterface $ad_entity */
  $ad_entity = $variables['ad_entity'];
  $settings = $ad_entity->getThirdPartySettings('ad_entity_adtech');

  // Generate attributes.
  $id = 'adtech-' . Crypt::randomBytesBase64(8);
  $attributes = new Attribute(['id' => $id]);
  $attributes->addClass('adtech-factory-ad');
  $attributes->addClass('adtech-default-view');
  if (!empty($settings['data_atf'])) {
    $attributes->setAttribute('data-atf', $settings['data_atf']);
  }
  if (!empty($settings['data_atf_format'])) {
    $attributes->setAttribute('data-atf-format', $settings['data_atf_format']);
  }
  $variables['attributes'] = $attributes;

  /** @var \Drupal\Core\Routing\AdminContext $admin_context */
  // Usually page targeting is already being included via
  // ad_entity_adtech_page_attachments(). If we are on an admin
  // page though, the page targeting needs to be manually included here.
  $admin_context = \Drupal::service('router.admin_context');
  if ($admin_context->isAdminRoute()) {
    if ($config = \Drupal::config('ad_entity.settings')) {
      $settings = $config->get('adtech_factory');
      if (!empty($settings['page_targeting'])) {
        // Include page targeting.
        $page_targeting = new TargetingCollection($settings['page_targeting']);
        $page_targeting->filter();
        $variables['#attached']['library'][] = 'ad_entity_adtech/page_targeting';
        $variables['#attached']['drupalSettings']['adtech_page_targeting'] = $page_targeting->toArray();
      }
    }
  }
}
