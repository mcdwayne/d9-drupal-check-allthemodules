<?php

namespace Drupal\invite\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverInterface;
use Drupal\invite\Entity\InviteSender;

/**
 * InviteBlock Class.
 */
class InviteBlock implements DeriverInterface {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    $derivatives = $this->getDerivativeDefinitions($base_plugin_definition);
    if (isset($derivatives[$derivative_id])) {
      return $derivatives[$derivative_id];
    }
  }

  /**
   * Creates a block for each sending method that is enabled on invite_types.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $derivatives = [];
    foreach (InviteSender::loadMultiple(\Drupal::entityQuery('invite_sender')
      ->condition('sending_methods', $base_plugin_definition['provider'], 'CONTAINS')
      ->execute()) as $sending_method) {
      $sending_method_id = $sending_method->id();
      $derivatives[$sending_method_id] = $base_plugin_definition;
      $derivatives[$sending_method_id]['admin_label'] = \Drupal::config('invite.invite_type.' . $sending_method_id)
        ->get('label');
    }

    return $derivatives;
  }

}
