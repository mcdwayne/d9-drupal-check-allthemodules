<?php

namespace Drupal\domain_googlenews\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\domain_googlenews\DomainGoogleNewsList;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for products routes.
 */
class GoogleNewsController extends ControllerBase {

  /**
   * The config object for the site settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The cache object.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The negotiator object.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The entitytypemanager object for node.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entitytypemanager;

  /**
   * The domainGoogleNewsList object for nodecount.
   *
   * @var \Drupal\domain_googlenews\DomainGoogleNewsList
   */
  protected $domainGoogleNewsList;

  /**
   * Implements __construct().
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache default object.
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   Domain negotiator object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entitytypemanager
   *   The entity object.
   * @param \Drupal\domain_googlenews\DomainGoogleNewsList $domainGoogleNewsList
   *   Googlenews list object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $cache, DomainNegotiatorInterface $negotiator, EntityTypeManagerInterface $entitytypemanager, DomainGoogleNewsList $domainGoogleNewsList) {
    $this->config = $config_factory;
    $this->cache = $cache;
    $this->domainNegotiator = $negotiator;
    $this->entityTypeManager = $entitytypemanager;
    $this->domainGoogleNewsList = $domainGoogleNewsList;
  }

  /**
   * Create function return static domain loader configuration.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Load the ContainerInterface.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cache.default'),
      $container->get('domain.negotiator'),
      $container->get('entity_type.manager'),
      $container->get('domain_googlenews.list')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getgooglenews() {
    $negotiator = $this->domainNegotiator;
    if ($negotiator->getActiveDomain()) {
      $domainID = $negotiator->getActiveDomain()->id();
    }
    $cid = $domainID . '-googlenews';
    $content = '';
    if ($cache = $this->cache->get($cid)) {
      // Verify the data hasn't expired.
      if (time() < $cache->expire) {
        $content = $cache->data;
      }
    }
    // If nothing loaded from the cache, build it now.
    if (empty($content)) {
      $config = $this->config('googlenews_admin.settings');
      $publication_name = $config->get('googlenews_publication_name');
      $list_nodes = $this->domainGoogleNewsList->domainGooglenewsListNodes();
      $content = '<?xml version="1.0" encoding="UTF-8"?>';
      $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">';
      if ($list_nodes) {
        foreach ($list_nodes as $record) {
          // Load the node.
          $node_storage = $this->entityTypeManager()->getStorage('node');
          $node = $node_storage->load($record->nid);
          $langcode = $node->language()->getId();
          $options = ['absolute' => TRUE];
          $url_obj = Url::fromRoute('entity.node.canonical', ['node' => $record->nid], $options);
          $url_string = $url_obj->toString();
          $content .= '<url>';
          $content .= '<loc>' . $url_string . '</loc>';
          $content .= '<news:news>';
          $content .= '<news:publication>';
          $content .= '<news:name>' . $publication_name . '</news:name>';
          $content .= '<news:language>' . $langcode . '</news:language>';
          $content .= '</news:publication>';
          $content .= '<news:title>' . $node->getTitle() . '</news:title>';
          $content .= '<news:publication_date>' . gmdate(DATE_W3C, $node->getCreatedTime()) . '</news:publication_date>';
          $content .= '</news:news>';
          $content .= '</url>';
        }
      }
      $content .= '</urlset>';
      $timeout = time() + (intval($config->get('googlenews_cache_timeout') != '' ? $config->get('googlenews_cache_timeout') : '15') * 60);
      $this->cache->set($cid, $content, $timeout);
    }
    $response = new Response($content, Response::HTTP_OK, ['content-type' => 'application/xml']);
    return $response;
  }

}
