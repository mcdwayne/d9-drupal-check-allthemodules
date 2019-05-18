<?php

namespace Drupal\block_aria_landmark_roles;

/**
 * Helper class for ARIA landmark roles.
 */
class BlockAriaLandmarkRoles {

  /**
   * An array of ARIA landmark roles.
   *
   * @var array
   */
  private $roles = [
    'application',
    'banner',
    'complementary',
    'contentinfo',
    'form',
    'main',
    'navigation',
    'search',
  ];

  /**
   * Get the defined ARIA roles.
   *
   * @return array
   *   An indexed array of roles.
   */
  public static function get() {
    return (new static())->roles;
  }

  /**
   * Get the defined ARIA roles as an associative array.
   *
   * @return array
   *   An associative array of roles.
   */
  public static function getAssociative() {
    $self = (new static());

    return array_combine($self->roles, $self->roles);
  }

}
