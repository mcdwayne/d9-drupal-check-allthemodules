<?php

namespace Drupal\og_sm;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgContextInterface;
use Drupal\og_sm\Event\SiteEvent;
use Drupal\og_sm\Event\SiteEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A manager to keep track of which nodes are og_sm Site enabled.
 */
class SiteManager implements SiteManagerInterface {

  /**
   * The site type manager.
   *
   * @var \Drupal\og_sm\SiteTypeManagerInterface
   */
  protected $siteTypeManager;

  /**
   * The OG context provider.
   *
   * @var \Drupal\og\OgContextInterface
   */
  protected $ogContext;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The service that contains the current active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * An array of previously loaded site homepage urls.
   *
   * @var array
   */
  protected $homepageUrls;

  /**
   * Constructs a SiteManager object.
   *
   * @param \Drupal\og_sm\SiteTypeManagerInterface $siteTypeManager
   *   The entity type manager.
   * @param \Drupal\og\OgContextInterface $ogContext
   *   The OG context provider.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\og\MembershipManagerInterface $membershipManager
   *   The membership manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountProxyInterface $accountProxy
   *   The service that contains the current active user.
   */
  public function __construct(SiteTypeManagerInterface $siteTypeManager, OgContextInterface $ogContext, EntityTypeManagerInterface $entityTypeManager, MembershipManagerInterface $membershipManager, EventDispatcherInterface $eventDispatcher, ModuleHandlerInterface $moduleHandler, AccountProxyInterface $accountProxy) {
    $this->siteTypeManager = $siteTypeManager;
    $this->ogContext = $ogContext;
    $this->entityTypeManager = $entityTypeManager;
    $this->membershipManager = $membershipManager;
    $this->eventDispatcher = $eventDispatcher;
    $this->moduleHandler = $moduleHandler;
    $this->accountProxy = $accountProxy;
  }

  /**
   * Gets the node storage object.
   *
   * @return \Drupal\node\NodeStorageInterface
   *   The node storage object.
   */
  protected function getNodeStorage() {
    return $this->entityTypeManager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   */
  public function isSite(NodeInterface $node) {
    return $this->siteTypeManager->isSiteTypeId($node->getType());
  }

  /**
   * {@inheritdoc}
   */
  public function currentSite() {
    $entity = $this->ogContext->getGroup();
    if (!$entity || !$entity instanceof NodeInterface || !$this->isSite($entity)) {
      return NULL;
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    $site = $this->getNodeStorage()->load($id);
    if (!$site || !$this->isSite($site)) {
      return FALSE;
    }
    return $site;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteHomePage(NodeInterface $site = NULL) {
    // Fallback to current Site.
    if (!$site) {
      $site = $this->currentSite();
    }

    // Only if there is a Site.
    if (!$site || !$this->isSite($site)) {
      return FALSE;
    }

    if (isset($this->homepageUrls[$site->id()])) {
      return $this->homepageUrls[$site->id()];
    }

    $routeName = 'entity.node.canonical';
    $routeParameters = ['node' => $site->id()];
    $this->moduleHandler->alter('og_sm_site_homepage', $site, $routeName, $routeParameters);
    $this->homepageUrls[$site->id()] = Url::fromRoute($routeName, $routeParameters);
    return $this->homepageUrls[$site->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function clearSiteCache(NodeInterface $site) {
    $this->eventDispatch(SiteEvents::CACHE_CLEAR, $site);
  }

  /**
   * {@inheritdoc}
   */
  public function eventDispatch($action, NodeInterface $node) {
    // Only for Site node types.
    if (!$this->isSite($node)) {
      return;
    }

    $event = new SiteEvent($node);
    $this->eventDispatcher->dispatch($action, $event);

    // Dispatch the save event for insert/update operations.
    $actions = [SiteEvents::INSERT, SiteEvents::UPDATE];
    if (in_array($action, $actions)) {
      $this->eventDispatch(SiteEvents::SAVE, $node);
    }

    // Register shutdown functions for post_op operations.
    $post_actions = [
      SiteEvents::INSERT => SiteEvents::POST_INSERT,
      SiteEvents::UPDATE => SiteEvents::POST_UPDATE,
      SiteEvents::SAVE => SiteEvents::POST_SAVE,
      SiteEvents::DELETE => SiteEvents::POST_DELETE,
    ];
    if (isset($post_actions[$action])) {
      drupal_register_shutdown_function(
        [$this, 'eventDispatch'],
        $post_actions[$action],
        $node
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAllSiteNodeIds() {
    $siteTypes = $this->siteTypeManager->getSiteTypes();
    if (!$siteTypes) {
      return [];
    }

    $query = $this->getNodeStorage()->getQuery()->condition('type', array_keys($siteTypes), 'IN');
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getAllSites() {
    $ids = $this->getAllSiteNodeIds();
    if (!$ids) {
      return [];
    }
    return $this->getNodeStorage()->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function filterSitesFromGroups(array $groups) {
    $sites = [];
    if (!isset($groups['node'])) {
      return $sites;
    }

    /* @var \Drupal\node\NodeInterface $group */
    foreach ($groups['node'] as $group) {
      if ($this->isSite($group)) {
        $sites[$group->id()] = $group;
      }
    }

    return $sites;
  }

  /**
   * {@inheritdoc}
   */
  public function getSitesFromContent(NodeInterface $node) {
    return $this->getSitesFromEntity($node);
  }

  /**
   * {@inheritdoc}
   */
  public function getSitesFromEntity(EntityInterface $node) {
    $groups = $this->membershipManager->getGroups($node);
    return $this->filterSitesFromGroups($groups);
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteFromContent(NodeInterface $node) {
    return $this->getSiteFromEntity($node);
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteFromEntity(EntityInterface $entity) {
    $sites = $this->getSitesFromEntity($entity);

    if (empty($sites)) {
      return FALSE;
    }
    return reset($sites);
  }

  /**
   * {@inheritdoc}
   */
  public function isSiteContent(EntityInterface $entity) {
    return (bool) $this->getSiteFromEntity($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function contentBelongsToSite(EntityInterface $entity, NodeInterface $site) {
    $sites = $this->getSitesFromEntity($entity);
    return !empty($sites[$site->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntitiesBySite(NodeInterface $site, $entity_type_id) {
    if (!$this->isSite($site)) {
      return [];
    }

    $entity_ids = $this->membershipManager->getGroupContentIds($site, [$entity_type_id]);
    if (empty($entity_ids[$entity_type_id])) {
      return [];
    }

    return $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple($entity_ids[$entity_type_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getUserSites(AccountInterface $account) {
    if ($account->hasPermission('administer group')) {
      return $this->getAllSites();
    }
    $groups = $this->membershipManager->getUserGroups($account);
    return $this->filterSitesFromGroups($groups);
  }

  /**
   * {@inheritdoc}
   */
  public function getUserManageableSites(AccountInterface $account = NULL) {
    if (!$account) {
      $account = $this->accountProxy->getAccount();
    }
    return $this->getUserSites($account);
  }

  /**
   * {@inheritdoc}
   */
  public function userIsMemberOfSite(AccountInterface $account, NodeInterface $site) {
    return (bool) $this->getUserMembership($account, $site);
  }

  /**
   * {@inheritdoc}
   */
  public function getUserMembership(AccountInterface $account, NodeInterface $site) {
    if (!$this->isSite($site)) {
      return NULL;
    }
    return $this->membershipManager->getMembership($site, $account);
  }

}
