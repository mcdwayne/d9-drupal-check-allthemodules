<?php

namespace Drupal\og_sm_menu;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og_sm\SiteManagerInterface;

/**
 * A manager to keep track of which nodes are og_sm Site enabled.
 */
class SiteMenuManager implements SiteMenuManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The site manager service.
   *
   * @var \Drupal\og_sm\SiteManagerInterface
   */
  protected $siteManager;

  /**
   * Constructs a SiteMenuManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager
   * @param \Drupal\og_sm\SiteManagerInterface $site_manager
   *   The site manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, SiteManagerInterface $site_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->siteManager = $site_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentMenu() {
    $site = $this->siteManager->currentSite();
    if (!$site) {
      return NULL;
    }

    return $this->getMenuBySite($site);
  }

  /**
   * {@inheritdoc}
   */
  public function getMenuBySite(NodeInterface $site) {
    $instances = $this->ogMenuInstanceStorage()->loadByProperties([
      'type' => SiteMenuManagerInterface::SITE_MENU_NAME,
      OgGroupAudienceHelperInterface::DEFAULT_FIELD => $site->id(),
    ]);

    if ($instances) {
      return array_pop($instances);
    }
    return NULL;
  }


  /**
   * {@inheritdoc}
   */
  public function createMenu(NodeInterface $site) {
    if ($this->getMenuBySite($site)) {
      return NULL;
    }

    $values = [
      'type' => SiteMenuManagerInterface::SITE_MENU_NAME,
      OgGroupAudienceHelperInterface::DEFAULT_FIELD => $site->id(),
    ];
    $og_menu_instance = $this->ogMenuInstanceStorage()->create($values);
    $og_menu_instance->save();

    return $og_menu_instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllMenus() {
    return $this->ogMenuInstanceStorage()->loadByProperties([
      'type' => SiteMenuManagerInterface::SITE_MENU_NAME,
    ]);
  }

  /**
   * Gets the og-menu instance storage object.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   */
  protected function ogMenuInstanceStorage() {
    return $this->entityTypeManager->getStorage('ogmenu_instance');
  }

}
