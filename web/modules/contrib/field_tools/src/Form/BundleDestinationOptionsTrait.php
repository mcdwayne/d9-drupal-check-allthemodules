<?php

namespace Drupal\field_tools\Form;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a helper method to create the bundle destinations form options.
 */
trait BundleDestinationOptionsTrait {

  /**
   * Gets the options for the destination entity types and bundles form element.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   *
   * @return
   *  An array of Form API options.
   */
  protected function getDestinationOptions(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $entity_types = $entity_type_manager->getDefinitions();
    $bundles = $entity_type_bundle_info->getAllBundleInfo();

    $destination_options = [];

    foreach ($entity_types as $entity_type_id => $entity_type) {
      // Only consider fieldable entity types.
      // As we're working with fields in the UI, only consider entity types that
      // have a field UI.
      if (!$entity_type->get('field_ui_base_route')) {
        continue;
      }

      $entity_type_label = $entity_type->getLabel();

      foreach ($bundles[$entity_type_id] as $bundle_id => $bundle_info) {
        // The option key for this entity type and bundle.
        $option_key = "$entity_type_id::$bundle_id";
        $destination_options[$option_key] = $entity_type_label . ' - ' . $bundle_info['label'];
      }
    }
    natcasesort($destination_options);

    return $destination_options;
  }

}
