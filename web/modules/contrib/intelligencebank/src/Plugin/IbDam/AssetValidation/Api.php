<?php

namespace Drupal\ib_dam\Plugin\IbDam\AssetValidation;

use Drupal\ib_dam\Asset\EmbedAsset;
use Drupal\ib_dam\Asset\LocalAsset;
use Drupal\ib_dam\AssetValidation\AssetValidationBase;
use Drupal\ib_dam\IbDamApi;

/**
 * Validates an asset based on passed api validators.
 *
 * @IbDamAssetValidation(
 *   id = "api",
 *   label = @Translation("Api validator")
 * )
 *
 * @package Drupal\ib_dam\Plugin\ibDam\AssetValidation
 */
class Api extends AssetValidationBase {

  /**
   * API auth key validator.
   *
   * @param \Drupal\ib_dam\Asset\LocalAsset $asset
   *   The asset object to validate.
   *
   * @return array
   *   An array with validation messages.
   */
  public function validateApiAuthKey(LocalAsset $asset) {
    $errors = [];

    if (empty($asset->source()->getAuthKey())) {
      $errors[] = $this->t("Missing Auth Key parameter. See <a href=':link' target='_blank'>documentation</a>", [
        ':link' => 'https://intelligencebank.atlassian.net/wiki/spaces/APIDOC/overview#APIDocumentation-ResourceRequest.1',
      ]);
    }
    return $errors;
  }

}
