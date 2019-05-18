<?php

namespace Drupal\field_tools;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldException;
use Drupal\field\FieldConfigInterface;

/**
 * Contains methods for cloning fields.
 */
class FieldCloner {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity query service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new FieldCloner.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueryFactory $entity_query, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->queryFactory = $entity_query;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Clone a field to a new entity type and bundle.
   *
   * It is assumed that the caller has already checked that no field of this
   * name exists on the destination bundle.
   * TODO: check for this and throw an exception.
   *
   * @param \Drupal\field\FieldConfigInterface $field_config
   *  The field config entity to clone.
   * @param string $destination_entity_type_id
   *  The entity type to clone the field to. If this is different from the
   *  source field, then one of the following will happen:
   *    - a) a new field storage is created
   *    - b) an existing field storage is used, if its type matches the source
   *      field.
   *    - c) an exception is thrown if the existing type does not match.
   * @param string $destination_bundle
   *  The destination bundle.
   *
   * @throws \Drupal\Core\Field\FieldException
   *  Throws an exception if there is already a field storage with the same name
   *  on the target entity type, whose type does not match the given field.
   */
  public function cloneField(FieldConfigInterface $field_config, $destination_entity_type_id, $destination_bundle) {
    // Get the entity type and bundle of the original field.
    $field_config_target_entity_type_id = $field_config->getTargetEntityTypeId();

    // If the destination entity type is different from the source field, also
    // clone the field storage.
    if ($destination_entity_type_id != $field_config_target_entity_type_id) {
      // Check there isn't already a field storage on the target entity type.
      $field_storage_config_ids = $this->queryFactory->get('field_storage_config')
        ->condition('entity_type', $destination_entity_type_id)
        ->condition('field_name', $field_config->getName())
        ->execute();
      if (empty($field_storage_config_ids)) {
        // Create a new field storage, copying the one from the source field.
        $source_field_storage_config = $field_config->getFieldStorageDefinition();

        $new_field_storage_config = $source_field_storage_config->createDuplicate();
        $new_field_storage_config->set('entity_type', $destination_entity_type_id);
        $new_field_storage_config->save();
      }
      else {
        // Load the existing field storage, and check its type.
        $existing_field_storage_config = $this->entityTypeManager->getStorage('field_storage_config')->load(reset($field_storage_config_ids));

        if ($existing_field_storage_config->getType() != $field_config->getType()) {
          throw new FieldException(t("A field with a different type already exists on destination entity type @entity-type.", [
            '@entity-type' => $destination_entity_type_id,
          ]));
        }
      }
    }

    // Create and save the duplicate field.
    $new_field_config = $field_config->createDuplicate();
    if ($destination_entity_type_id != $field_config_target_entity_type_id) {
      $new_field_config->set('entity_type', $destination_entity_type_id);
    }
    $new_field_config->set('bundle', $destination_bundle);
    $new_field_config->save();

    // Copy the field's display settings to the destination bundle's displays,
    // where possible.
    $this->copyDisplayComponents('entity_form_display', $field_config, $destination_entity_type_id, $destination_bundle);
    $this->copyDisplayComponents('entity_view_display', $field_config, $destination_entity_type_id, $destination_bundle);
  }

  /**
   * Copy the field's display settings to the destination bundle's displays.
   *
   * This finds displays with the same name and copies the original field's
   * settings to them. So for example, if the source bundle has a 'teaser' view
   * mode and so does the destination bundle, the settings will be copied from
   * one to the other.
   *
   * @param string $display_type
   *  The entity type ID of the display entities to copy: one of
   *  'entity_view_display' or entity_form_display'.
   * @param \Drupal\field\FieldConfigInterface $field_config
   *  The field config entity to clone.
   * @param string $destination_entity_type_id
   *  The destination entity type.
   * @param string $destination_bundle
   *  The destination bundle.
   */
  protected function copyDisplayComponents($display_type, FieldConfigInterface $field_config, $destination_entity_type_id, $destination_bundle) {
    $field_name = $field_config->getName();
    $field_config_target_entity_type_id = $field_config->getTargetEntityTypeId();
    $field_config_target_bundle = $field_config->getTargetBundle();

    // Get the view displays on the source entity bundle.
    $display_ids = $this->queryFactory->get($display_type)
      ->condition('targetEntityType', $field_config_target_entity_type_id)
      ->condition('bundle', $field_config_target_bundle)
      ->execute();
    $original_field_bundle_displays = $this->entityTypeManager->getStorage($display_type)->loadMultiple($display_ids);

    // Get the views displays on the destination's target entity bundle.
    $display_ids = $this->queryFactory->get($display_type)
      ->condition('targetEntityType', $destination_entity_type_id)
      ->condition('bundle', $destination_bundle)
      ->execute();
    $displays = $this->entityTypeManager->getStorage($display_type)->loadMultiple($display_ids);
    // Re-key this array by the mode name.
    $duplicate_field_bundle_displays = [];
    foreach ($displays as $display) {
      $duplicate_field_bundle_displays[$display->getMode()] = $display;
    }

    // Work over the original field's view displays.
    foreach ($original_field_bundle_displays as $display) {
      // If the destination bundle doesn't have a display of the same name,
      // skip this.
      if (!isset($duplicate_field_bundle_displays[$display->getMode()])) {
        continue;
      }

      $destination_display = $duplicate_field_bundle_displays[$display->getMode()];

      // Get the settings for the field in this display.
      $field_component = $display->getComponent($field_name);

      // Copy the settings to the duplicate field's view mode with the same
      // name.
      if (is_null($field_component)) {
        // Explicitly hide the field, so it's set in the display.
        $destination_display->removeComponent($field_name);
      }
      else {
        $destination_display->setComponent($field_name, $field_component);
      }

      // Copy field groups.
      if ($this->moduleHandler->moduleExists('field_group')){
        $source_display_field_group_settings = $display->getThirdPartySettings('field_group');
        $destination_display_field_group_settings = $destination_display->getThirdPartySettings('field_group');

        // Attempt to find the field in one of the groups.
        foreach ($source_display_field_group_settings as $group_id => $group_settings) {
          if (in_array($field_name, $group_settings['children'])) {
            // Insert the field into the field group of the same name on the
            // destination, creating the field group if necessary.

            // Clone the field group if it's not there already.
            if (!isset($destination_display_field_group_settings[$group_id])) {
              $field_group_copy = $group_settings;
              // Remove the children so the group starts off empty.
              $field_group_copy['children'] = [];

              $destination_display_field_group_settings[$group_id] = $field_group_copy;
            }

            // Splice the new field into the destination field group, attempting
            // to use the same position.
            $position = array_search($field_name, $group_settings['children']);
            array_splice($destination_display_field_group_settings[$group_id]['children'], $position, 0, [$field_name]);

            // Update the field group settings on the destination display.
            $destination_display->setThirdPartySetting('field_group', $group_id, $destination_display_field_group_settings[$group_id]);

            // The field can be in only one group, so we're done: stop looking
            // at groups.
            break;
          }
        }
      }

      $destination_display->save();
    }
  }

}
