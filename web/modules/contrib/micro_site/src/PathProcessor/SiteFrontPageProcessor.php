<?php

namespace Drupal\micro_site\PathProcessor;

use Drupal\commerce_url\EncryptDecrypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use \Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Process the Inbound and Outbound checkout urls.
 */
class SiteFrontPageProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * An alias manager for looking up the system path.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * A config factory for retrieving required config settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The Site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Constructs a PathProcessorAlias object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   An alias manager for looking up the system path.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   *
   * @param \Drupal\micro_site\SiteNegotiatorInterface $site_negotiator
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AliasManagerInterface $alias_manager, ConfigFactoryInterface $config_factory, SiteNegotiatorInterface $site_negotiator ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->aliasManager = $alias_manager;
    $this->config = $config_factory;
    $this->negotiator = $site_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $exception = $request->get('exception');

    if ($path === '/') {
      // Check if a site exists and has the site url requested.
      $http_post = $request->server->get('HTTP_HOST');
      $public_url = $this->config->get('micro_site.settings')->get('public_url');
      if ($http_post == $public_url) {
        // Nothing to do.
        return $path;
      }

      $base_url = $this->config->get('micro_site.settings')->get('base_url');
      if ($http_post == $base_url) {
        // Nothing to do.
        return $path;
      }

      if ($http_post == 'localhost') {
        return $path;
      }
      /** @var \Drupal\micro_site\Entity\SiteInterface $site */
      if ($site = $this->negotiator->getActiveSite()) {
        $path = '/site/' . $site->id();
      }
      else {
        throw new NotFoundHttpException;
      }
    }
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if ($request) {
      $exception = $request->get('exception');
    }

    if (preg_match('/^\/site\/([0-9]+)$/i', $path, $matches)) {
      $site_id = $matches['1'];
      $site = $this->negotiator->loadById($site_id);
      $active_site = $this->negotiator->getActiveSite();
      if ($site instanceof SiteInterface && $active_site && $active_site->id() == $site->id()) {
        $path = '/';
      }
    }

    return $path;
  }


}
