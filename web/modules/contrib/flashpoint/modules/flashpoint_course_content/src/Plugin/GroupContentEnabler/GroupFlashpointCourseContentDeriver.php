<?php

namespace Drupal\flashpoint_course_content\Plugin\GroupContentEnabler;

use Drupal\flashpoint_course_content\Entity\FlashpointCourseContentType;
use Drupal\Component\Plugin\Derivative\DeriverBase;

class GroupFlashpointCourseContentDeriver extends DeriverBase {

  /**
   * {@inheritdoc}.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach (FlashpointCourseContentType::loadMultiple() as $name => $flashpoint_course_content_type) {
      $label = $flashpoint_course_content_type->label();

      $this->derivatives[$name] = [
        'entity_bundle' => $name,
        'label' => t('Group course content (@type)', ['@type' => $label]),
        'description' => t('Adds %type content to groups both publicly and privately.', ['%type' => $label]),
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
