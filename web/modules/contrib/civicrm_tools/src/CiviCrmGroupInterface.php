<?php

namespace Drupal\civicrm_tools;

/**
 * Interface CiviCrmGroupInterface.
 */
interface CiviCrmGroupInterface {

  /**
   * Get a group by id.
   *
   * @param int $group_id
   *   CiviCRM group id.
   *
   * @return array
   *   CiviCRM group.
   */
  public function getGroup($group_id);

  /**
   * Get all groups.
   *
   * @return array
   *   List of groups.
   */
  public function getAllGroups();

  /**
   * Get all groups for a contact.
   *
   * @param int $contact_id
   *   CiviCRM contact id.
   * @param bool $load
   *   Load the CiviCRM Group.
   *
   * @return array
   *   List of groups id's.
   */
  public function getGroupsFromContact($contact_id, $load);

}
