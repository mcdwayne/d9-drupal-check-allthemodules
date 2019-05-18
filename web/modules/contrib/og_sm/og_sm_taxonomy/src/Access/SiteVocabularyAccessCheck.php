<?php

namespace Drupal\og_sm_taxonomy\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\og\OgAccessInterface;
use Drupal\og_sm_taxonomy\SiteTaxonomyManagerInterface;

/**
 * Determines access to for taxonomy operations within site context.
 */
class SiteVocabularyAccessCheck implements AccessInterface {

  /**
   * The site taxonomy manager.
   *
   * @var \Drupal\og_sm_taxonomy\SiteTaxonomyManagerInterface
   */
  protected $siteTaxonomyManager;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Constructs a new SiteVocabularyAccessCheck.
   *
   * @param \Drupal\og_sm_taxonomy\SiteTaxonomyManagerInterface $site_taxonomy_manager
   *   The site taxonomy manager.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   */
  public function __construct(SiteTaxonomyManagerInterface $site_taxonomy_manager, OgAccessInterface $og_access) {
    $this->siteTaxonomyManager = $site_taxonomy_manager;
    $this->ogAccess = $og_access;
  }

  /**
   * Checks access for a site taxonomy vocabulary.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\node\NodeInterface $node
   *   The site node.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, NodeInterface $node) {
    if ($account->hasPermission('administer taxonomy')) {
      return AccessResult::allowed();
    }

    if ($this->ogAccess->userAccess($node, 'administer taxonomy', $account)->isAllowed()) {
      return AccessResult::allowed();
    }

    foreach ($this->siteTaxonomyManager->getSiteVocabularyNames() as $vocabulary_name) {
      $access = $this->ogAccess->userAccess($node, "update any $vocabulary_name taxonomy_term", $account);
      $access->orIf($this->ogAccess->userAccess($node, "update own $vocabulary_name taxonomy_term", $account));
      if ($access->isAllowed()) {
        return AccessResult::allowed();
      }
    }

    return AccessResult::neutral();
  }

}
