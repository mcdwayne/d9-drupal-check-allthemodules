<?php

namespace Drupal\domain_traversal;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\domain\Entity\Domain;
use Drupal\user\UserInterface;

class DomainTraversal implements DomainTraversalInterface {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface $domain_storage
   */
  protected $domain_storage;

  /**
   * Whether the domain_access module is enabled.
   *
   * @var bool
   */
  protected $domain_access_enabled;

  /**
   * The domains access manager service.
   *
   * @var \Drupal\domain_access\DomainAccessManagerInterface
   */
  protected $domain_access;

  /**
   * DomainTraversal constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->domain_storage = $entity_type_manager->getStorage('domain');
    $this->domain_access_enabled = \Drupal::moduleHandler()->moduleExists('domain_access');

    if ($this->domain_access_enabled) {
      $this->domain_access = \Drupal::service('domain_access.manager');
    }
  }

  /**
   * @inheritdoc
   */
  public function getAccountTraversableDomainIds(UserInterface $account) {
    $account_domain_ids = NULL;
    if ($this->domain_access_enabled && !$this->accountMayTraverseAllDomains($account)) {
      $account_domain_ids = array_keys($this->domain_access->getAccessValues($account));
    }

    $domain_ids = [];

    /** @var Domain $domain */
    foreach ($this->domain_storage->loadMultiple($account_domain_ids) as $domain) {
      if (!$domain->status()) {
        continue;
      }
      $domain_ids[$domain->id()] = $domain->id();
    }

    return $domain_ids;
  }

  /**
   * @inheritdoc
   */
  public function accountMayTraverseDomain(UserInterface $account, Domain $domain) {
    if ($account->isAnonymous()) {
      return FALSE;
    }

    if ($this->accountMayTraverseAllDomains($account)) {
      return TRUE;
    }

    $domain_ids = $this->getAccountTraversableDomainIds($account);

    return isset($domain_ids[$domain->id()]);
  }

  /**
   * @inheritdoc
   */
  public function accountMayTraverseAllDomains(UserInterface $account) {
    if ($account->isAnonymous()) {
      return FALSE;
    }

    if ($account->hasPermission('traverse all domains')) {
      return TRUE;
    }

    if ($this->domain_access_enabled && !empty($this->domain_access->getAllValue($account))) {
      return TRUE;
    }

    return FALSE;
  }
}
