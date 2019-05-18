<?php

/**
 * @file
 * Hooks for ad_entity module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Tells the ad_entity module what this integration is capable of.
 *
 * Modules which implement ad integrations using ad_entity
 * should implement this hook.
 *
 * Following keys should be returned:
 * - personalization: TRUE in case ads can be personalized,
 *   e.g. via cookie-based re-targeting, FALSE otherwise.
 * - consent_aware: TRUE in case this integration is consent
 *   aware, i.e. personalization would be disabled when
 *   no consent exists or when the user declined a consent.
 *   The ad_entity module provides a generic way to determine
 *   whether a consent exists. To adapt it, have a look at
 *   the global settings and the ad_entity/base library.
 *   Set FALSE if this module does not use ad_entity functions
 *   regards consent awareness and/or disabling personalization.
 *
 * Ad integrations which use personalization, but don't support
 * consent awareness via ad_entity functionality, must take care
 * on their own to be compliant with existing privacy protection laws.
 *
 * @return array
 *   The module info as associative array.
 */
function hook_ad_entity_module_info() {
  return [
    'personalization' => TRUE,
    'consent_aware' => TRUE,
  ];
}

/**
 * Alter the ad_entity Javascript settings.
 *
 * This might be helpful if you need to add further settings
 * for Javascript which runs very early, e.g. when directly using
 * ad_entity/base before any ad_entity/view is being run.
 *
 * @param array &$settings
 *   The JS settings array.
 * @param array &$cache_tags
 *   The cache tags for caching the settings.
 */
function hook_ad_entity_js_settings_alter(array &$settings, array &$cache_tags) {
  $settings['foo'] = 'bar';
}

/**
 * Modify the render array of the given Advertising entity.
 *
 * @param array &$build
 *   The render array of the given Advertising entity.
 */
function hook_ad_entity_view_alter(array &$build, \Drupal\ad_entity\Entity\AdEntityInterface $ad_entity) {
  if ($ad_entity->isNew()) {
    // Class attributes will be merged during preprocessing.
    $ad_entity->_attributes['class'][] = 'new';
  }
}

/**
 * Act on the inclusion of Advertising context.
 *
 * @param \Drupal\Core\Field\FieldItemListInterface $items
 *   A list of field items containing Advertising context,
 *   which is about to be included for appliance.
 * @param array $settings
 *   The settings of the formatter being used for the list of field items.
 */
function hook_ad_context_include(\Drupal\Core\Field\FieldItemListInterface $items, array $settings) {
  $to_append['context'] = [
    'context_plugin_id' => 'targeting',
    'apply_on' => [],
    'context_settings' => [
      'targeting' => [
        'targeting' => [
          'entityid' => $items->getEntity()->id(),
        ],
      ],
    ],
  ];
  $items->appendItem($to_append);
}

/**
 * Act on resetting the backend context data for the given entity.
 *
 * For more information, see AdContextManager::resetContextDataForEntity().
 *
 * @param \Drupal\ad_entity\Plugin\AdContextManager $context_manager
 *   The manager for Advertising context plugins and backend context data.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity, e.g. a node, for which the context data has been reset.
 */
function hook_ad_context_data_reset(\Drupal\ad_entity\Plugin\AdContextManager $context_manager, \Drupal\Core\Entity\EntityInterface $entity) {
  $context_manager
    ->addContextData('targeting', ['targeting' => ['key' => 'value']]);
}

/**
 * @} End of "addtogroup hooks".
 */
