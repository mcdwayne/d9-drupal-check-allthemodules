<?php

namespace Drupal\yasm\Services;

use Drupal\group\Entity\GroupInterface;

/**
 * Defines groups statistics interface.
 */
interface GroupsStatisticsInterface {

  /**
   * Count group nodes.
   */
  public function countNodes(GroupInterface $group);

  /**
   * Count group nodes grouping by content type.
   */
  public function countNodesByType(GroupInterface $group);

  /**
   * Count group members.
   */
  public function countMembers(GroupInterface $group);

  /**
   * Count group members grouping by role.
   */
  public function countMembersByRole(GroupInterface $group);

  /**
   * Get all group roles.
   */
  public function getGroupRoles();

}
