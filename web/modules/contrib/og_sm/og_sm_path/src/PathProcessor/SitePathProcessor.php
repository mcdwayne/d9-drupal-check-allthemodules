<?php

namespace Drupal\og_sm_path\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\og_sm\SiteManagerInterface;
use Drupal\og_sm_path\Event\AjaxPathEvent;
use Drupal\og_sm_path\Event\AjaxPathEvents;
use Drupal\og_sm_path\SitePathManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Path processor manager.
 */
class SitePathProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

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
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * An array of ajax paths.
   *
   * @var string[]
   */
  protected $ajaxPaths;

  /**
   * Constructs a SitePathProcessor object.
   *
   * @param \Drupal\og_sm_path\SitePathManagerInterface $site_path_manager
   *   The site path manager.
   * @param \Drupal\og_sm\SiteManagerInterface $site_manager
   *   The site manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(SitePathManagerInterface $site_path_manager, SiteManagerInterface $site_manager, EventDispatcherInterface $event_dispatcher) {
    $this->sitePathManager = $site_path_manager;
    $this->siteManager = $site_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Returns an array of paths that should be rewritten to have site context.
   *
   * @return array
   *   An array of ajax paths.
   */
  protected function ajaxPaths() {
    if ($this->ajaxPaths !== NULL) {
      return $this->ajaxPaths;
    }

    $event = new AjaxPathEvent();
    $this->eventDispatcher->dispatch(AjaxPathEvents::COLLECT, $event);
    $this->ajaxPaths = $event->getAjaxPaths();
    return $this->ajaxPaths;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    // Translate an admin path without alias back to its original path.
    if (preg_match('#^([\w/_-]+)/(admin.*)#', $path, $parts)) {
      $site = $this->sitePathManager->getSiteFromPath($parts[1]);
      if ($site) {
        $path = sprintf('/group/node/%d/%s', $site->id(), $parts[2]);
      }
    }
    // Translate a system path back to normal path.
    elseif (preg_match('#^([\w/_-]+)(' . implode('|', $this->ajaxPaths()) . ')$#', $path, $parts)) {
      $site = $this->sitePathManager->getSiteFromPath($parts[1]);
      if ($site) {
        $path = $parts[2];

        $request->query->set('og_sm_context_site_id', $site->id());
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
    if (preg_match('#^/group/node/([0-9]+)/(admin.*)#', $path, $parts)) {
      $site = $this->siteManager->load($parts[1]);
      if ($site) {
        $path = $this->sitePathManager->getPathFromSite($site) . '/' . $parts[2];
      }
    }
    // Only check specific paths in a Site context.
    elseif ($site = $this->siteManager->currentSite()) {
      if (preg_match('#^(' . implode('|', $this->ajaxPaths()) . ')$#', $path, $parts)) {
        $path = $this->sitePathManager->getPathFromSite($site) . $parts[1];
      }
    }

    // This will check replace any destination (in the options > query) by its
    // path alias. Note: this will affect links outside a Site as well. We can
    // have links outside a Site context with a destination that is in a Site.
    if (isset($options['query']['destination'])) {
      $base_path = $request ? $request->getBasePath() . '/' : '/';
      $destination = $options['query']['destination'];
      if (strpos($destination, $base_path) === 0) {
        $destination = substr($destination, strlen($base_path));
      }
      $destination = ltrim($destination, '/');
      $parts = parse_url($destination);
      $alias = Url::fromUserInput('/' . $parts['path']);
      if (!empty($parts['query'])) {
        $alias->setOption('query', $parts['query']);
      }
      $options['query']['destination'] = $alias->toString();
    }
    return $path;
  }

}
