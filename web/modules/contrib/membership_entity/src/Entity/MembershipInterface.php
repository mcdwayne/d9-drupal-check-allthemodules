<?php
declare(strict_types=1);

namespace Drupal\membership_entity\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining Membership entities.
 *
 * @ingroup membership_entity
 */
interface MembershipInterface extends ContentEntityInterface {
  /**
   * Gets the Membership Member ID.
   *
   * @return string
   *   Member ID of the Membership.
   */
  public function getMemberID(): string;

  /**
   * Sets the Membership Member ID.
   *
   * @param string $member_id
   *   The Membership Member ID.
   *
   * @return \Drupal\membership_entity\Entity\MembershipInterface
   *   The called Membership entity.
   */
  public function setMemberID(string $member_id): MembershipInterface;

  /**
   * Gets the Membership creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Membership.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the Membership creation timestamp.
   *
   * @param int $timestamp
   *   The Membership creation timestamp.
   *
   * @return \Drupal\membership_entity\Entity\MembershipInterface
   *   The called Membership entity.
   */
  function setCreatedTime(int $timestamp): MembershipInterface;
}
