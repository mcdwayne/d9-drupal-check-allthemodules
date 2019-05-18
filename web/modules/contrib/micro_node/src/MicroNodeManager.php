<?php

namespace Drupal\micro_node;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\micro_site\SiteUsers;
use Drupal\micro_node\MicroNodeFields;

/**
 * {@inheritdoc}
 */
class MicroNodeManager implements MicroNodeManagerInterface {

  /**
   * Name of the field which references others sites.
   */
  const NODE_SITE = 'field_sites';

  /**
   * The HTTP_HOST value of the request.
   */
  protected $httpHost;

  /**
   * The site record returned by the lookup request.
   *
   * @var \Drupal\micro_site\Entity\SiteInterface
   */
  protected $site;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;


  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Constructs a DomainNegotiator object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack object.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Domain loader object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\micro_site\SiteNegotiatorInterface $site_negotiator
   *   The site negotiator.
   */
  public function __construct(RequestStack $requestStack, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, SiteNegotiatorInterface $site_negotiator) {
    $this->requestStack = $requestStack;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->negotiator = $site_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCurrentSiteId() {
    /** @var \Drupal\micro_site\Entity\SiteInterface $site */
    $site = \Drupal::service('micro_site.negotiator')->getActiveSite();

    // We are not on a active site url. Try to load it from the Request.
    if (empty($site)) {
      $site = \Drupal::service('micro_site.negotiator')->loadFromRequest();
    }
    return ($site) ? [$site->id()] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSitesByOwner(AccountInterface $account) {
    $query = $this->entityTypeManager->getStorage('site')->getQuery();
    $query->condition('user_id', $account->id());
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getCountSitesByOwner(AccountInterface $account) {
    $query = $this->entityTypeManager->getStorage('site')->getQuery();
    $query->condition('user_id', $account->id());
    return $query->count()->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getSitesReferencingUserPerRole(AccountInterface $account, $field_name = SiteUsers::MICRO_SITE_ADMINISTRATOR) {
    $query = $this->entityTypeManager->getStorage('site')->getQuery();
    $query->condition($field_name, $account->id(), 'IN');
    $result = $query->execute();
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getSitesReferencingUsers(AccountInterface $account) {
    $sites = $this->getSitesByOwner($account);
    $sites += $this->getSitesReferencingUserPerRole($account, SiteUsers::MICRO_SITE_ADMINISTRATOR);
    $sites += $this->getSitesReferencingUserPerRole($account, SiteUsers::MICRO_SITE_MANAGER);
    $sites += $this->getSitesReferencingUserPerRole($account, SiteUsers::MICRO_SITE_CONTRIBUTOR);
    $sites += $this->getSitesReferencingUserPerRole($account, SiteUsers::MICRO_SITE_MEMBER);
    return ($sites) ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSitesReferencingAdminUsers(AccountInterface $account) {
    $sites = $this->getSitesByOwner($account);
    $sites += $this->getSitesReferencingUserPerRole($account, SiteUsers::MICRO_SITE_ADMINISTRATOR);
    $sites += $this->getSitesReferencingUserPerRole($account, SiteUsers::MICRO_SITE_MANAGER);
    return ($sites) ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function getMainSiteFromEntity(EntityInterface $node) {
    $site = $node->get('site_id')->referencedEntities();
    if ($site) {
      $site = reset($site);
    }
    return $site;
  }

  /**
   * @inheritdoc
   */
  public function getSecondarySitesFromEntity(EntityInterface $entity, $field_name = MicroNodeFields::NODE_SITES) {
    $list = [];
    if (!$entity->hasField($field_name)) {
      return $list;
    }
    $values = $entity->get($field_name);
    if (!empty($values)) {
      foreach ($values as $item) {
        if ($target = $item->getValue()) {
          if ($site = $this->negotiator->loadById($target['target_id'])) {
            $list[$site->id()] = $site;
          }
        }
      }
    }
    return $list;
  }

  /**
   * @inheritdoc
   */
  public function isPublishedOnAllSites(EntityInterface $entity) {
    if (!$entity->hasField(MicroNodeFields::NODE_SITES_ALL)) {
      return FALSE;
    }
    $value = $entity->{MicroNodeFields::NODE_SITES_ALL}->value;
    return $value ? TRUE : FALSE;
  }

  /**
   * @inheritdoc
   */
  public function onMultipleSites(EntityInterface $entity, $field_name = MicroNodeFields::NODE_SITES) {
    return !empty($this->getSecondarySitesFromEntity($entity, $field_name)) || $this->isPublishedOnAllSites($entity);
  }

  /**
   * @inheritdoc
   */
  public function hasMultipleCanonicalUrl(EntityInterface $entity, $field_name = MicroNodeFields::NODE_SITES_DISABLE_CANONICAL_URL) {
    if (!$entity->hasField($field_name)) {
      return FALSE;
    }
    $unique_canonical_url_disabled = ($entity->{$field_name}->value) ? TRUE : FALSE;
    return $this->onMultipleSites($entity) && $unique_canonical_url_disabled;
  }

  /**
   * @inheritdoc
   */
  public function getMasterHostBaseUrl() {
    $micro_site_settings = $this->configFactory->get('micro_site.settings');
    $base_url = $micro_site_settings->get('base_scheme') . '://' . $micro_site_settings->get('public_url');
    return $base_url;
  }

  /**
   * @inheritdoc
   */
  public function getAllSitesFromEntity(EntityInterface $entity, $field_name = MicroNodeFields::NODE_SITES) {
    $values = [];
    if ($main_site = $this->getMainSiteFromEntity($entity)) {
      $values[$main_site->id()] = $main_site;
    }

    $values += $this->getSecondarySitesFromEntity($entity, $field_name);

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getSitesUserCanReference(AccountInterface $account) {
    return $this->getSitesReferencingAdminUsers($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getSitesUserCanUpdateAdministrativeFields(AccountInterface $account) {
    return $this->getSitesReferencingAdminUsers($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getSitesUserCanUpdateAnyContent(AccountInterface $account) {
    return $this->getSitesReferencingAdminUsers($account);
  }

  /**
   * {@inheritdoc}
   */
  public function userCanCrossPublish(AccountInterface $account, SiteInterface $site = NULL) {
    if ($site) {
      $users_allowed = $site->getAdminUsersId();
      $users_allowed += $site->getManagerUsersId();
      $users_allowed += [$site->getOwnerId() => $site->getOwnerId()];
      return in_array($account->id(), $users_allowed) && $account->hasPermission('publish on any assigned site');
    }
    return $account->hasPermission('publish on any site');
  }

  /**
   * {@inheritdoc}
   */
  public function userCanCreateContent(AccountInterface $account, SiteInterface $site = NULL) {
    if ($site) {
      $users_allowed = $site->getAdminUsersId();
      $users_allowed += $site->getManagerUsersId();
      $users_allowed += $site->getContributorUsersId();
      $users_allowed += [$site->getOwnerId() => $site->getOwnerId()];
      return in_array($account->id(), $users_allowed) && $account->hasPermission('publish on any assigned site');
    }
    return $account->hasPermission('administer site entities');
  }

  /**
   * {@inheritdoc}
   */
  public function userCanAccessContentOverview(AccountInterface $account, SiteInterface $site = NULL) {
    return $this->userCanCreateContent($account, $site);
  }

  /**
   * {@inheritdoc}
   */
  public function nodeAccess(NodeInterface $node, $op, AccountInterface $account) {

    $site = $this->getMainSiteFromEntity($node);
    if (empty($site)) {
      // Global permissions apply.
      return AccessResult::neutral('Node not associated with a site entity.')
        ->addCacheableDependency($node);
    }

    // We assume now that the node is associated with a site entity.
    // Check global permissions.
    if ($account->hasPermission('administer site entities')) {
      return AccessResult::allowed()
        ->cachePerPermissions()
        ->cachePerUser();
    }

    if ($account->hasPermission('administer own site entity') && $site->getOwnerId() == $account->id()) {
      return AccessResult::allowed()
        ->cachePerPermissions()
        ->cachePerUser()
        ->addCacheableDependency($site)
        ->addCacheableDependency($node);
    }

    // Site not published. Deny access to its nodes except for site owner and users.
    if (!$site->isPublished()) {
      $users_site = $site->getAllUsersId();
      if (!($site->getOwnerId() == $account->id() || in_array($account->id(), $users_site))) {
        return AccessResult::forbidden('site is not published, deny access to its nodes.')
          ->cachePerUser()
          ->addCacheableDependency($node)
          ->addCacheableDependency($site);
      }
    }

    return AccessResult::neutral();

  }

}
