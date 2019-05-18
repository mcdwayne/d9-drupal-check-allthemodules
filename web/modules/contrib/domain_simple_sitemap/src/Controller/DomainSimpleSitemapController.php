<?php

namespace Drupal\domain_simple_sitemap\Controller;

use Drupal\Core\Database\Connection;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\simple_sitemap\Controller\SimplesitemapController;
use Drupal\simple_sitemap\Simplesitemap;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\CacheableResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DomainSimpleSitemapController.
 *
 * @package Drupal\simple_sitemap\Controller
 */
class DomainSimpleSitemapController extends SimplesitemapController {

  /**
   * The domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * Database Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * {@inheritdoc}
   */
  public function __construct(Simplesitemap $generator, KillSwitch $cache_kill_switch, DomainNegotiatorInterface $domain_negotiator, Connection $db) {
    parent::__construct($generator, $cache_kill_switch);
    $this->domainNegotiator = $domain_negotiator;
    $this->db = $db;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_sitemap.generator'),
      $container->get('page_cache_kill_switch'),
      $container->get('domain.negotiator'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSitemap($chunk_id = NULL) {
    $response = parent::getSitemap($chunk_id);
    $meta_data = $response->getCacheableMetadata();
    $meta_data->addCacheTags($this->domainNegotiator->getActiveDomain()->getCacheTags());
    $meta_data->addCacheContexts(['url.site']);
    $response->addCacheableDependency($this->domainNegotiator->getActiveId());
    return $response;
  }

}
