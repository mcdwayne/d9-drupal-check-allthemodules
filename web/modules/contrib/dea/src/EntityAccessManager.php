<?php

namespace Drupal\dea;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Implementation of TermAccessManagerInterface.
 */
class EntityAccessManager {

  /**
   * @var \Drupal\dea\GrantDiscovery
   */
  protected $grantManager;

  /**
   * @var \Drupal\dea\RequirementDiscovery
   */
  protected $requirementManager;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig $config
   */
  protected $config;

  /**
   * @var String $access_check_boolean_operator
   */

  /**
   * @param \Drupal\dea\RequirementDiscovery $requirement_manager
   * @param \Drupal\dea\GrantDiscovery $grant_manager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(
    RequirementDiscovery $requirement_manager,
    GrantDiscovery $grant_manager,
    ConfigFactoryInterface $config_factory
  ) {
    $this->requirementManager = $requirement_manager;
    $this->grantManager = $grant_manager;
    $this->config       = $config_factory->get('dea.settings');
    $this->access_check_boolean_operator = $this->config->get('access_check_boolean_operator');
  }


  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account) {
    // If the user has permission, ignore DEA access check.
    if ($account->hasPermission('bypass dea access check')) {
      return AccessResult::neutral();
    }

    // Build a list of requirement strings from entity requirements.
    $requirements = array_map(function (EntityInterface $entity) {
      return $entity->getEntityTypeId() . ':' . $entity->id();
    }, $this->requirementManager->requirements($entity, $entity, $operation));

    // If there are no requirements, ignore the access check.
    if (count($requirements) == 0) {
      return AccessResult::neutral();
    }

    // Build a list of grant strings from the current accounts grants.
    $grants = array_map(function (EntityInterface $entity) {
      return $entity->getEntityTypeId() . ':' . $entity->id();
    }, $this->grantManager->grants($account, $entity, $operation));

    if ($this->access_check_boolean_operator == "AND") {
      // All requirements should pass, otherwise deny access.
      foreach ($requirements as $requirement) {
        if (!in_array($requirement, $grants)) {
          return AccessResult::forbidden();
        }
      }
      return AccessResult::allowed();
    } else {
      // If grants and requirements overlap allow, else deny access.
      if (count(array_intersect($requirements, $grants)) > 0) {
        return AccessResult::allowed();
      }
      else {
        return AccessResult::forbidden();
      }
    }
  }

}
