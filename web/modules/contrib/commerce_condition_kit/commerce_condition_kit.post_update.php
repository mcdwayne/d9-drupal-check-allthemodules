<?php

/**
 * @file
 * Post update functions for Commerce Condition Kit.
 */

/**
 * Add the user_usage_limit field to coupons.
 */
function commerce_condition_kit_post_update_add_coupon_user_usage_limit() {
  $entity_definition_update = \Drupal::entityDefinitionUpdateManager();
  $coupon_definition = $entity_definition_update->getEntityType('commerce_promotion_coupon');
  $fields = commerce_condition_kit_entity_base_field_info($coupon_definition);
  $entity_definition_update->installFieldStorageDefinition('user_usage_limit', 'commerce_promotion_coupon', 'commerce_condition_kit', $fields['user_usage_limit']);
}
