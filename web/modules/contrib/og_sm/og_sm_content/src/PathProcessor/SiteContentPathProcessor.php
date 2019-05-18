<?php

namespace Drupal\og_sm_content\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\og_sm\SiteManagerInterface;
use Drupal\og_sm_path\SitePathManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Path processor manager.
 */
class SiteContentPathProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * The site path manager.
   *
   * @var \Drupal\og_sm_path\SitePathManagerInterface
   */
  protected $sitePathManager;

  /**
   * The site manager.
   *
   * @var \Drupal\og_sm\SiteManagerInterface
   */
  protected $siteManager;

  /**
   * Constructs a SiteContentPathProcessor object.
   *
   * @param \Drupal\og_sm_path\SitePathManagerInterface $site_path_manager
   *   The site path manager.
   * @param \Drupal\og_sm\SiteManagerInterface $site_manager
   *   The site manager.
   */
  public function __construct(SitePathManagerInterface $site_path_manager, SiteManagerInterface $site_manager) {
    $this->sitePathManager = $site_path_manager;
    $this->siteManager = $site_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    // Translate an admin path without alias back to its original path.
    if (preg_match('#^([\w/_-]+)(/content.*)#', $path, $parts)) {
      $site = $this->sitePathManager->getSiteFromPath($parts[1]);
      if ($site) {
        $path = sprintf('/group/node/%d%s', $site->id(), $parts[2]);
      }
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    // Rewrite all outgoing site admin paths for paths that do not have an
    // alias.
    if (preg_match('#^/group/node/([0-9]+)(/content.*)#', $path, $parts)) {
      $site = $this->siteManager->load($parts[1]);
      if ($site) {
        $path = $this->sitePathManager->getPathFromSite($site) . $parts[2];
      }
    }

    return $path;
  }

}
