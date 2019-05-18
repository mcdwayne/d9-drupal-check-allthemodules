<?php

namespace Drupal\og_sm_path\Plugin\OgGroupResolver;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\og\OgGroupResolverBase;
use Drupal\og\OgResolvedGroupCollectionInterface;
use Drupal\og_sm_path\SitePathManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tries to get the context based on the first part of the URL.
 *
 * If the first part is the path of a Site, that Site becomes the active
 * context.
 *
 * @OgGroupResolver(
 *   id = "og_sm_context_path",
 *   label = "Site Path",
 *   description = @Translation("Determine Site context based on the fact that the current URL starts with the Site path of a Site node.")
 * )
 */
class PathGroupResolver extends OgGroupResolverBase implements ContainerFactoryPluginInterface {

  /**
   * The query argument that holds the site node ID.
   */
  const SITE_ID_ARGUMENT = 'og_sm_context_site_id';

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The site path manager.
   *
   * @var \Drupal\og_sm_path\SitePathManagerInterface
   */
  protected $sitePathManager;

  /**
   * Constructs a AdminGroupResolver.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\og_sm_path\SitePathManagerInterface $site_path_manager
   *   The site path manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Request $request, SitePathManagerInterface $site_path_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->request = $request;
    $this->sitePathManager = $site_path_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('og_sm.path.site_path_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(OgResolvedGroupCollectionInterface $collection) {
    // Get the alias or URL outbound alter of the current URL.
    $path = $this->request->getPathInfo();
    $alias = $this->sitePathManager->lookupPathAlias($path);
    $path = $alias ?: $path;
    $path = trim($path, '/');
    $parts = explode('/', $path);
    $site_path = reset($parts);
    $site = $this->sitePathManager->getSiteFromPath('/' . $site_path);

    if ($site) {
      $collection->addGroup($site, ['url']);
      $this->stopPropagation();
    }
  }

}
