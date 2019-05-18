<?php

namespace Drupal\flashpoint_course_module\Plugin\GroupContentEnabler;

use Drupal\Component\Plugin\Derivative\DeriverBase;

class FlashpointCourseGroupFlashpointCourseModuleDeriver extends DeriverBase {

  /**
   * {@inheritdoc}.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // I used to set 'entity_bundle' to 'user' and the autocomplete for the user name never worked.
    // Checking a user with devel, the bundle is  always empty.
    // So leave it empty and specify the entity_type_id = "user" in the docblock
    // of UserEntity.php
    $this->derivatives['flashpoint_course_module'] = [
        'entity_bundle' => 'flashpoint_course_module',
        'label' => t('Course Module'),
        'description' => t('Adds flashpoint_course_module entities to groups.'),
      ] + $base_plugin_definition;
    return $this->derivatives;
  }

}
