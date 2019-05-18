<?php

/**
 * @file
 *
 * Post update functions for commerce_store_domain.
 */

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Sets the cardinality of the `domain` field to unlimited.
 */
function commerce_store_domain_post_update_3010033_domain_cardinality() {
  $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  $store_storage = \Drupal::entityTypeManager()->getStorage('commerce_store');
  $store_domains = [];

  // Store and remove previous values so we can change the cardinality.
  /** @var \Drupal\commerce_store\Entity\StoreInterface $store */
  foreach ($store_storage->loadMultiple() as $store) {
    $store_domains[$store->id()] = $store->get('domain')->getValue();
    $store->get('domain')->setValue(NULL);
    $store->save();
  }

  $field_definition = $definition_update_manager->getFieldStorageDefinition('domain', 'commerce_store');
  $field_definition->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED);
  $definition_update_manager->updateFieldStorageDefinition($field_definition);

  // Set the domain values, again.
  /** @var \Drupal\commerce_store\Entity\StoreInterface $store */
  foreach ($store_storage->loadMultiple() as $store) {
    $store->get('domain')->setValue($store_domains[$store->id()]);
    $store->save();
  }
}

/**
 * Install domain_entity reference field for Domain module support.
 */
function commerce_store_domain_post_update_3001600_domain_module_support() {
  $module_handler = \Drupal::moduleHandler();
  if ($module_handler->moduleExists('domain')) {
    $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
    $field_definition = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Domain'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'domain')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', TRUE);

    $definition_update_manager->installFieldStorageDefinition(
      'domain_entity',
      'commerce_store',
      'commerce_store_domain',
      $field_definition
    );
  }
}
