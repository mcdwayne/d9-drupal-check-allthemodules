<?php

namespace Drupal\og_sm_context\Plugin\OgGroupResolver;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\og\OgGroupResolverBase;
use Drupal\og\OgResolvedGroupCollectionInterface;
use Drupal\og_sm\SiteManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tries to get the context based on a Site ID in a get parameter.
 *
 * Will check if there is a get parameter "og_sm_context_site_id" with a valid
 * Site Node ID.
 *
 * @OgGroupResolver(
 *   id = "og_sm_context_get",
 *   label = "Site Get parameter",
 *   description = @Translation("Determine Site context based on the fact that there is a GET parameter 'og_sm_context_site_id' set.")
 * )
 */
class QueryParamGroupResolver extends OgGroupResolverBase implements ContainerFactoryPluginInterface {

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
   * The site manager.
   *
   * @var \Drupal\og_sm\SiteManagerInterface
   */
  protected $siteManager;

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
   * @param \Drupal\og_sm\SiteManagerInterface $site_manager
   *   The site manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Request $request, SiteManagerInterface $site_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->request = $request;
    $this->siteManager = $site_manager;
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
      $container->get('og_sm.site_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(OgResolvedGroupCollectionInterface $collection) {

    $query = $this->request->query;
    if (!$query->has(self::SITE_ID_ARGUMENT)) {
      return;
    }

    $site_id = $query->get(self::SITE_ID_ARGUMENT);
    $site = $this->siteManager->load($site_id);
    if ($site) {
      $collection->addGroup($site, ['url']);
      $this->stopPropagation();
    }
  }

}
