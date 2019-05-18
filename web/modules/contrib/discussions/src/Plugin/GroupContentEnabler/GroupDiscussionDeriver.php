<?php

namespace Drupal\discussions\Plugin\GroupContentEnabler;

use Drupal\discussions\Entity\DiscussionType;
use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Provide additional definitions based on an existing definition.
 */
class GroupDiscussionDeriver extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach (DiscussionType::loadMultiple() as $name => $discussion_type) {
      $label = $discussion_type->label();

      $this->derivatives[$name] = [
        'entity_bundle' => $name,
        'label' => t('Group Discussion') . " ($label)",
        'description' => t('Adds %type discussions to groups.', ['%type' => $label]),
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
