<?php

namespace Drupal\civicrm_tools;

use Drupal\civicrm\Civicrm;
use Drupal\Core\Database\Database;

/**
 * Class CiviCrmGroup.
 */
class CiviCrmGroup implements CiviCrmGroupInterface, CiviCrmEntityFormatInterface {

  /**
   * Drupal\civicrm\Civicrm definition.
   *
   * @var \Drupal\civicrm\Civicrm
   */
  protected $civicrm;

  /**
   * Drupal\civicrm_tools\CiviCrmApiInterface definition.
   *
   * @var \Drupal\civicrm_tools\CiviCrmApiInterface
   */
  protected $civiCrmApi;

  /**
   * Constructs a new CiviCrmGroup object.
   */
  public function __construct(Civicrm $civicrm, CiviCrmApiInterface $civicrm_api) {
    $this->civicrm = $civicrm;
    $this->civiCrmApi = $civicrm_api;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup($group_id) {
    $result = [];
    $filter['id'] = $group_id;
    $group = $this->civiCrmApi->get('Group', $filter);
    if (!empty($group)) {
      $result = $group[$group_id];
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllGroups() {
    $result = [];
    // @fixme getAll does not return all the groups, only the first 25.
    // currently relying on the database for that.
    // return $this->civiCrmApi->getAll('Group', []);
    /** @var \Drupal\civicrm_tools\CiviCrmDatabaseInterface $civiCrmDatabase */
    $civiCrmDatabase = \Drupal::service('civicrm_tools.database');
    $queryResult = $civiCrmDatabase->execute(
      "SELECT * FROM {civicrm_group}"
    );
    foreach ($queryResult as $row) {
      $group = $this->civiCrmApi->get('Group', ['id' => $row->id]);
      $result[$row->id] = $group[$row->id];
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupsFromContact($contact_id, $load = TRUE) {
    $result = [];
    // @todo use CiviCrmDatabase
    Database::setActiveConnection('civicrm');
    $db = Database::getConnection();
    $query = $db->query("SELECT group_id FROM {civicrm_group_contact} WHERE contact_id = :contact_id AND status = :status", [
      ':contact_id' => $contact_id,
      ':status' => 'Added',
    ]);
    $queryResult = $query->fetchAll();
    // Switch back to the default database.
    Database::setActiveConnection();

    if ($load) {
      foreach ($queryResult as $row) {
        $group = $this->civiCrmApi->get('Group', ['group_id' => $row->group_id]);
        $result[$row->group_id] = $group;
      }
    }
    else {
      foreach ($queryResult as $row) {
        $result[$row->group_id] = $row->group_id;
      }
    }
    // https://issues.civicrm.org/jira/browse/CRM-20711
    // https://issues.civicrm.org/jira/browse/CRM-18675
    // $params = [
    // 'contact_id' => $contact_id,
    // ];
    // $groups = $this->civiCrmApi->getAll('GroupContact', $params);
    // if(!empty($groups)) {
    // $result = $groups;
    // }.
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function labelFormat(array $values) {
    // TODO: Implement labelFormat() method.
    return [];
  }

}
