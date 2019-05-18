<?php

namespace Drupal\flashpoint_community_content\Plugin\GroupContentEnabler;

use Drupal\flashpoint_community_content\Entity\FlashpointCommunityContentType;
use Drupal\Component\Plugin\Derivative\DeriverBase;

class GroupFlashpointCommunityContentDeriver extends DeriverBase {

  /**
   * {@inheritdoc}.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach (FlashpointCommunityContentType::loadMultiple() as $name => $flashpoint_community_c_type) {
      $label = $flashpoint_community_c_type->label();

      $this->derivatives[$name] = [
        'entity_bundle' => $name,
        'label' => t('Group community content (@type)', ['@type' => $label]),
        'description' => t('Adds %type content to groups both publicly and privately.', ['%type' => $label]),
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
