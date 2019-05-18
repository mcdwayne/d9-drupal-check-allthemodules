<?php
declare(strict_types=1);

namespace Drupal\membership_entity\Entity\MembershipType;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Membership type entities.
 */
interface MembershipTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the membership type description.
   *
   * @return string
   *  The membership type description value.
   */
  public function description();

}
