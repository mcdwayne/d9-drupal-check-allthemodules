<?php

namespace Drupal\contactlist\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteTagsWidget;

/**
 * Plugin implementation of the 'contact_group_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "contact_group_autocomplete",
 *   label = @Translation("Contact Group Autocomplete"),
 *   description = @Translation("An autocomplete text field for contact groups."),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ContactGroupAutocompleteWidget extends EntityReferenceAutocompleteTagsWidget {

  /**
   * Returns the name of the bundle which will be used for autocreated entities.
   *
   * This method ignores the 'target_bundles' selection handler setting and uses
   * the 'auto_create_bundle' setting instead.
   *
   * @return string
   *   The bundle name.
   */
  protected function getAutocreateBundle() {
    $bundle = NULL;
    if ($this->getSelectionHandlerSetting('auto_create') && !$bundle = $this->getSelectionHandlerSetting('auto_create_bundle')) {
      // If no bundle has been set as auto create target means that there is
      // an inconsistency in entity reference field settings.
      trigger_error(sprintf(
        "The 'Create referenced entities if they don't already exist' option is enabled but a specific destination bundle is not set. You should re-visit and fix the settings of the '%s' (%s) field.",
        $this->fieldDefinition->getLabel(),
        $this->fieldDefinition->getName()
      ), E_USER_WARNING);
    }

    return $bundle;
  }

}
