<?php

namespace Drupal\crm_core_user_sync;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\crm_core_contact\IndividualInterface;
use Drupal\user\UserInterface;

/**
 * CrmCoreUserSyncRelation service.
 */
class CrmCoreUserSyncRelationRules {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  protected $rules;

  /**
   * Constructs a CrmCoreUserSyncRelationRules object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param string $configName
   *   Name of the configuration object that stores rules.
   */
  public function __construct(ConfigFactoryInterface $config_factory, $configName) {
    $this->configFactory = $config_factory;
    $this->configName = $configName;
  }

  /**
   * Retrieves the individual contact id for specified user.
   *
   * @return int|null
   *   Individual id, if relation exists.
   */
  protected function getRules() {
    if ($this->rules === NULL) {
      $rules = $this
        ->configFactory
        ->get($this->configName)
        ->get('rules');

      uasort($rules, [$this, 'weightCmp']);
      $this->rules = $rules;
    }

    return $this->rules;
  }

  /**
   * Rules weight comparison function.
   */
  protected function weightCmp(array $a, array $b) {
    if ($a['weight'] == $b['weight']) {
      return 0;
    }
    return ($a['weight'] < $b['weight']) ? -1 : 1;
  }

  /**
   * Checks if provided contact can be linked to this account.
   *
   * @param \Drupal\user\UserInterface $account
   *   User  account to check.
   * @param \Drupal\crm_core_contact\IndividualInterface $contact
   *   Contact record to check.
   *
   * @return bool
   *   TRUE if the contact is valid.
   */
  public function valid(UserInterface $account, IndividualInterface $contact) {
    return $contact->bundle() === $this->getContactType($account);
  }

  /**
   * Get contact type resolved from configured synchronization rules.
   *
   * @param \Drupal\user\UserInterface $account
   *   User account to check.
   *
   * @return string|false
   *   Intividual contact type (bundle) to be user for this user account.
   */
  public function getContactType(UserInterface $account) {

    foreach ($this->getRules() as $rule) {
      if ($rule['enabled'] && $account->hasRole($rule['role'])) {
        return $rule['contact_type'];
      }
    }

    return FALSE;
  }

}
