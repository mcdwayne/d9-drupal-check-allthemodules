<?php

namespace Drupal\campaignmonitor_campaign;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;

/**
 * Provides dynamic permissions for nodes of different types.
 */
class CampaignMonitorCampaignPermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Returns an array of node type permissions.
   *
   * @return array
   *   The node type permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function campaignMonitorPermissions() {
    $perms = [];
    // Generate node permissions for all node types.
    foreach (NodeType::loadMultiple() as $type) {
      if (campaignmonitor_campaign_type_is_configured($type->id())) {
        $perms += $this->buildPermissions($type);
      }

    }

    return $perms;
  }

  /**
   * Returns a list of node permissions for a given node type.
   *
   * @param \Drupal\node\Entity\NodeType $type
   *   The node type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(NodeType $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "send $type_id campaigns" => [
        'title' => $this->t('%type_name: Send Campaign', $type_params),
      ],
    ];
  }

}
