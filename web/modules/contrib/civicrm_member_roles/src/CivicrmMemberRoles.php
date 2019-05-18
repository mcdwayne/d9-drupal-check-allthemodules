<?php

namespace Drupal\civicrm_member_roles;

use Drupal\civicrm\Civicrm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Class CivicrmMemberRoles.
 */
class CivicrmMemberRoles {

  /**
   * CiviCRM service.
   *
   * @var \Drupal\civicrm\Civicrm
   */
  protected $civicrm;

  /**
   * CiviCRM member roles configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Inactive status IDs.
   *
   * Call ::getInactiveStatusIds instead of directly accessing this property.
   *
   * @var array|null
   */
  protected $inactiveStatusIds;

  /**
   * CivicrmMemberRoles constructor.
   *
   * @param \Drupal\civicrm\Civicrm $civicrm
   *   CiviCRM service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(Civicrm $civicrm, ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager) {
    $this->civicrm = $civicrm;
    $this->config = $configFactory->get('civicrm_member_roles.settings');
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Gets membership statuses.
   *
   * @return array
   *   Membership statuses, keyed by ID.
   */
  public function getStatuses() {
    $statuses = [];

    $this->civicrm->initialize();
    $result = civicrm_api3('MembershipStatus', 'get');
    if (empty($result['values'])) {
      return $statuses;
    }

    foreach ($result['values'] as $value) {
      $statuses[$value['id']] = $value['name'];
    }

    return $statuses;
  }

  /**
   * Get information for a membership type.
   *
   * @param int $id
   *   The type ID.
   *
   * @return array|null
   *   The type, or NULL if not found.
   */
  public function getType($id) {
    try {
      $this->civicrm->initialize();
      $result = civicrm_api3('MembershipType', 'getsingle', ['id' => $id]);
    }
    catch (\Exception $e) {
      $result = NULL;
    }

    return $result;
  }

  /**
   * Gets membership types.
   *
   * @return array
   *   Membership types, keyed by ID.
   */
  public function getTypes() {
    $types = [];

    $this->civicrm->initialize();
    $result = civicrm_api3('MembershipType', 'get');
    if (empty($result['values'])) {
      return $types;
    }

    foreach ($result['values'] as $value) {
      $types[$value['id']] = $value['name'];
    }

    return $types;
  }

  /**
   * Sync user roles to membership status.
   *
   * @param int|null $limit
   *   A limit for the number of contacts to sync.
   */
  public function sync($limit = NULL) {
    foreach ($this->getSyncContactIds($limit) as $cid) {
      if ($account = $this->getContactAccount($cid)) {
        $this->syncContact($cid, $account);
      }
    }
  }

  /**
   * Get contact IDs to sync.
   *
   * @param int|null $limit
   *   A limit for the number of IDs to return.
   *
   * @return array
   *   Contact IDs to sync.
   */
  public function getSyncContactIds($limit = NULL) {
    // Gather all of the contact types we have rules for.
    $types = [];
    foreach ($this->getRules() as $rule) {
      $types[] = $rule->getType();
    }
    $types = array_unique($types);

    // If no types, bail.
    if (!$types) {
      return $types;
    }

    // Find contacts with applicable types.
    $select = $this->getDatabase()
      ->select('civicrm_uf_match', 'uf')
      ->fields('uf', ['contact_id']);
    $select->leftJoin('civicrm_membership', 'm', 'uf.contact_id = m.contact_id');
    $select->isNotNull('m.id')->condition('m.membership_type_id', $types, 'IN');

    if ($limit) {
      $select->range(0, $limit)->orderRandom();
    }

    return $select->execute()->fetchCol();
  }

  /**
   * Loads all assignment rules.
   *
   * @return \Drupal\civicrm_member_roles\Entity\CivicrmMemberRoleRuleInterface[]
   *   The assignment rules.
   */
  protected function getRules() {
    return $this->entityTypeManager->getStorage('civicrm_member_role_rule')->loadMultiple();
  }

  /**
   * Sync membership roles for a user account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   */
  public function syncUser(AccountInterface $account) {
    if (!$contactId = $this->getUserContactId($account)) {
      return;
    }

    $this->syncContact($contactId, $account);
  }

  /**
   * Obtain the contact for a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return int|null
   *   The contact ID, or NULL if not found.
   */
  public function getUserContactId(AccountInterface $account) {
    try {
      $params = ['uf_id' => $account->id()];
      $this->civicrm->initialize();
      $result = civicrm_api3('UFMatch', 'getsingle', $params);
    }
    catch (\Exception $e) {
      return NULL;
    }

    return $result['contact_id'];
  }

  /**
   * Obtain the user account for a contact.
   *
   * @param int $cid
   *   The contact ID.
   *
   * @return \Drupal\Core\Session\AccountInterface|null
   *   The contact ID, or NULL if not found.
   */
  public function getContactAccount($cid) {
    try {
      $this->civicrm->initialize();
      $params = ['contact_id' => $cid];
      $result = civicrm_api3('UFMatch', 'getsingle', $params);
    }
    catch (\Exception $e) {
      return NULL;
    }

    return $this->entityTypeManager->getStorage('user')->load($result['uf_id']);
  }

  /**
   * Get membership data for a contact.
   *
   * @param int $contactId
   *   The contact ID.
   *
   * @return array
   *   Contact membership data.
   */
  protected function getContactMemberships($contactId) {
    $params = [
      'contact_id' => $contactId,
      'options' => ['limit' => 0],
    ];

    try {
      $this->civicrm->initialize();
      $result = civicrm_api3('membership', 'get', $params);
    }
    catch (\Exception $e) {
      return [];
    }

    return $result['values'];
  }

  /**
   * Syncs the role for the user related to a contact.
   *
   * @param int $cid
   *   The contact ID.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   */
  public function syncContact($cid, AccountInterface $account) {
    $rules = $this->getRules();
    $memberships = $this->getContactMemberships($cid);

    // CRM-16000 remove inactive memberships if member has both active and
    // inactive memberships.
    if (count($memberships) > 1) {
      $memberships = array_filter($memberships, function ($item) {
        return !in_array($item['status_id'], $this->getInactiveStatusIds());
      });
    }

    // The inital set of roles assigned to the user.
    $userRoles = $account->getRoles();

    if (empty($memberships)) {
      // Remove the roles managed by CiviCRM memberships.
      $newRoles = array_diff($userRoles, $this->getRulesRoles($rules));
    }
    else {
      // Remove expired roles, then set additional roles.
      $newRoles = array_diff($userRoles, $this->getExpiredRoles($rules, $memberships));
      $newRoles = array_unique(array_merge($newRoles, $this->getAddRoles($rules, $memberships)));
    }

    // If changes to the user roles were made, save it.
    if ($userRoles != $newRoles) {
      $user = $this->getAccountUser($account);
      $user->roles = $newRoles;
      $user->save();
    }
  }

  /**
   * Gets IDs of inactive statuses.
   *
   * @return array
   *   An array of IDs for inactive statuses.
   */
  protected function getInactiveStatusIds() {
    if ($this->inactiveStatusIds === NULL) {
      $this->inactiveStatusIds = [];

      try {
        $params = [
          'sequential' => 1,
          'name' => ['IN' => ['Deceased', 'Cancelled', 'Pending', 'Expired']],
        ];
        $this->civicrm->initialize();
        $result = civicrm_api3('MembershipStatus', 'get', $params);
        $this->inactiveStatusIds = array_map(function ($item) {
          return $item['id'];
        }, $result['values']);
      }
      catch (\Exception $e) {
        $this->inactiveStatusIds = [];
      }
    }

    return $this->inactiveStatusIds;
  }

  /**
   * Finds roles used in a set of assignment rules.
   *
   * @param \Drupal\civicrm_member_roles\Entity\CivicrmMemberRoleRuleInterface[] $rules
   *   Assignment rules.
   *
   * @return array
   *   The roles found in the assignment rules.
   */
  protected function getRulesRoles(array $rules) {
    $roles = [];

    foreach ($rules as $rule) {
      $roles[] = $rule->getRole();
    }

    return array_unique($roles);
  }

  /**
   * Gets the user for a user account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity.
   */
  protected function getAccountUser(AccountInterface $account) {
    if ($account instanceof UserInterface) {
      return $account;
    }

    return $this->entityTypeManager->getStorage('user')->load($account->id());
  }

  /**
   * Gets roles to add for a contact's memberships.
   *
   * @param \Drupal\civicrm_member_roles\Entity\CivicrmMemberRoleRuleInterface[] $rules
   *   Assignment rules.
   * @param array $memberships
   *   Contact membership data.
   *
   * @return array
   *   The roles to add for a contact.
   */
  protected function getAddRoles(array $rules, array $memberships) {
    $roles = [];

    foreach ($memberships as $membership) {
      // Find rules applicable to the membership type.
      $membershipRules = array_filter($rules, function ($rule) use ($membership) {
        /* @var \Drupal\civicrm_member_roles\Entity\CivicrmMemberRoleRuleInterface */
        return $rule->getType() == $membership['membership_type_id'];
      });
      foreach ($membershipRules as $rule) {
        if (in_array($membership['status_id'], $rule->getCurrentStatuses())) {
          $roles[] = $rule->getRole();
        }
      }
    }

    return array_unique($roles);
  }

  /**
   * Gets roles to expire for a contact's memberships.
   *
   * @param \Drupal\civicrm_member_roles\Entity\CivicrmMemberRoleRuleInterface[] $rules
   *   Assignment rules.
   * @param array $memberships
   *   Contact membership data.
   *
   * @return array
   *   The roles to expire for a contact.
   */
  protected function getExpiredRoles(array $rules, array $memberships) {
    $roles = [];

    foreach ($memberships as $membership) {
      // Find rules applicable to the membership type.
      $membershipRules = array_filter($rules, function ($rule) use ($membership) {
        /* @var \Drupal\civicrm_member_roles\Entity\CivicrmMemberRoleRuleInterface */
        return $rule->getType() == $membership['membership_type_id'];
      });
      foreach ($membershipRules as $rule) {
        if (in_array($membership['status_id'], $rule->getExpiredStatuses())) {
          $roles[] = $rule->getRole();
        }
      }
    }

    return array_unique($roles);
  }

  /**
   * Gets the database.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database.
   */
  protected function getDatabase() {
    return \Drupal::database();
  }

}
