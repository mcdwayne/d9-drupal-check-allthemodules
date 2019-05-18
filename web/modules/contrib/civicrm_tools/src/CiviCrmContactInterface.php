<?php

namespace Drupal\civicrm_tools;

/**
 * Interface CiviCrmContactInterface.
 */
interface CiviCrmContactInterface {

  /**
   * Get all contacts from a single Smart Groups.
   *
   * @param string $group_id
   *   CiviCRM smart group id.
   * @param array $params
   *   Optional parameters.
   *
   * @return array
   *   List of values.
   */
  public function getFromSmartGroup($group_id, array $params);

  /**
   * Get all contacts from a list of Groups.
   *
   * @param array $groups
   *   CiviCRM list of Group id's.
   *
   * @return array
   *   List of contacts.
   */
  public function getFromGroups(array $groups);

  /**
   * Get the first contact match from a Drupal user id.
   *
   * This is a common operation and the CiviCRM UFMatch
   * could not immediately ring a bell to new API users,
   * so providing a sugar for that.
   * It wraps the 2 API calls to UFMatch and Contact.
   *
   * @param int $uid
   *   The Drupal user id.
   * @param int $domain_id
   *   The CiviCRM domain id.
   *
   * @return array
   *   Array of data for a contact.
   */
  public function getFromUserId($uid, $domain_id);

  /**
   * Get the first contact match from the Drupal current logged in user.
   *
   * @param int $domain_id
   *   The CiviCRM domain id.
   *
   * @return array
   *   Array of data for a contact.
   */
  public function getFromLoggedInUser($domain_id);

  /**
   * Get the first user match from a CiviCRM contact id.
   *
   * @param int $cid
   *   The CiviCRM Contact id.
   * @param int $domain_id
   *   The CiviCRM domain id.
   *
   * @return null|\Drupal\user\Entity\User
   *   Drupal user entity.
   */
  public function getUserFromContactId($cid, $domain_id);

}
