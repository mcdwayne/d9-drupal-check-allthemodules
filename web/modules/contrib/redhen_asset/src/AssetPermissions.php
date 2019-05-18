<?php
/**
 * @file
 * Contains \Drupal\redhen_asset\AssetPermissions.
 */


namespace Drupal\redhen_asset;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\redhen_asset\Entity\AssetType;

class AssetPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of RedHen asset type permissions.
   *
   * @return array
   *    Returns an array of permissions.
   */
  public function AssetTypePermissions() {
    $perms = [];
    // Generate asset permissions for all asset types.
    foreach (AssetType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Builds a standard list of permissions for a given asset type.
   *
   * @param \Drupal\redhen_asset\Entity\AssetType $asset_type
   *   The machine name of the asset type.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function buildPermissions(AssetType $asset_type) {
    $type_id = $asset_type->id();
    $type_params = ['%type' => $asset_type->label()];

    return [
      "add $type_id asset" => [
        'title' => $this->t('%type: Add asset', $type_params),
      ],
      "view active $type_id asset" => [
        'title' => $this->t('%type: View active assets', $type_params),
      ],
      "view inactive $type_id asset" => [
        'title' => $this->t('%type: View inactive assets', $type_params),
      ],
      "edit $type_id asset" => [
        'title' => $this->t('%type: Edit asset', $type_params),
      ],
      "delete $type_id asset" => [
        'title' => $this->t('%type: Delete asset', $type_params),
      ],
    ];
  }

}
