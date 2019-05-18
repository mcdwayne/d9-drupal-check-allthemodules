<?php

namespace Drupal\embed_image_style_permissions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\image\Entity\ImageStyle;

/**
 * Defines dynamic permissions.
 *
 * @ingroup embed_image_style_permissions
 */
class PermissionsGenerator {
  use StringTranslationTrait;

  /**
   * Returns an array of entity type permissions.
   *
   * @return array
   *   The permissions.
   */
  public function stylePermissions() {
    return array_reduce(ImageStyle::loadMultiple(), [$this, 'buildPermissions'], []);
  }

  /**
   * Builds a list of entity permissions for a given type.
   *
   * @param array $carry
   *   The result of the previous iteration.
   * @param \Drupal\image\Entity\ImageStyle $style
   *   The entity type.
   *
   * @return array
   *   An array of permissions.
   */
  private function buildPermissions(array $carry, ImageStyle $style) {
    return array_merge($carry, [
      'access image style for ' . $style->id() => [
        'title' => $this->t('Access image style %style', ['%style' => $style->label()]),
      ],
    ]);
  }

}
