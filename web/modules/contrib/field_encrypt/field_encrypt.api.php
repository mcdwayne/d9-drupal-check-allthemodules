<?php
/**
 * @file
 * Hooks for Field Encrypt module.
 */

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Hook to alter values that will be stored in the unencrypted field storage.
 *
 * When a field gets encrypted, the unencrypted field storage gets the value
 * "[ENCRYPTED]" by default, to indicate there is data for the field, but it's
 * stored encrypted. For some field types this value would not be accepted, so
 * this hook makes it possible to store an alternative value for specific field
 * types.
 *
 * @param string &$unencrypted_storage_value
 *   The unencrypted field storage value to alter.
 * @param array $context
 *   An associative array with the following values:
 *   - "entity": \Drupal\Core\Entity\ContentEntityInterface
 *     The entity containing the field.
 *   - "field": \Drupal\Core\Field\FieldItemListInterface
 *     The field for which to store the unencrypted storage value.
 *   - "property": string
 *     The property for which to store the unencrypted storage value.
 */
function hook_field_encrypt_unencrypted_storage_value_alter(&$unencrypted_storage_value, $context) {
  $entity = $context['entity'];
  $field = $context['field'];
  $property = $context['property'];

  if ($entity->getEntityTypeId() == "node") {
    $field_type = $field->getFieldDefinition()->getType();
    if ($field_type == "text_with_summary") {
      if ($property == "summary") {
        $unencrypted_storage_value = "[ENCRYPTED SUMMARY]";
      }
    }
  }
}

/**
 * Hook to specify if a field property on a given entity should be encrypted.
 *
 * Allows other modules to specify whether a specific field property should
 * not be encrypted by field_encrypt module, regardless of the field encryption
 * settings set in the field storage configuration.
 *
 * If conditions are met where a field property should not be encrypted, return
 * FALSE in your hook implementation.
 *
 * Note: this only stops the encryption of a field that was set up to be
 * encrypted. It does not allow a field without field encryption settings to be
 * encrypted, because there are no settings defined to do so.
 *
 * @param \Drupal\Core\Entity\ContentEntityInterface $entity
 *   The entity to encrypt fields on.
 * @param string $field_name
 *   The field name to update.
 * @param string $delta
 *   The field delta.
 * @param string $property_name
 *   The field property name.
 *
 * @return bool
 *   Return FALSE if field property should not be encrypted.
 */
function hook_field_encrypt_allow_encryption(\Drupal\Core\Entity\ContentEntityInterface $entity, $field_name, $delta, $property_name) {
  // Only encrypt fields on unpublished nodes.
  if ($entity instanceof \Drupal\node\Entity\Node) {
    if ($entity->isPublished()) {
      return FALSE;
    }
  }
}
