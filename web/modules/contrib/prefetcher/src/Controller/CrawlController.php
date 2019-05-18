<?php

namespace Drupal\prefetcher\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\prefetcher\Entity\PrefetcherUriInterface;
use Drupal\prefetcher\PrefetcherCrawlerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CrawlController.
 *
 * @package Drupal\prefetcher\Controller
 */
class CrawlController extends ControllerBase {

  /**
   * @var \Drupal\prefetcher\PrefetcherCrawlerManager $crawler_manager
   */
  protected $crawler_manager;

  /**
   * Crawl.
   *
   * @return array
   */
  public function crawl(PrefetcherUriInterface $prefetcher_uri) {

    $crawler = $this->getCrawlerManager()->getDefaultCrawler();
    $crawler->crawl($prefetcher_uri);

    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: crawl with parameter(s): $prefetcher_uri: @label', ['@label' => $prefetcher_uri->label()]),
    ];
  }

  public static function create(ContainerInterface $container) {
    $crawler_manager = $container->get('plugin.manager.prefetcher_crawler');
    return new static($crawler_manager);
  }

  public function __construct(PrefetcherCrawlerManager $crawler_manager) {
    $this->setCrawlerManager($crawler_manager);
  }

  /**
   * @return \Drupal\prefetcher\PrefetcherCrawlerManager
   */
  public function getCrawlerManager() {
    return $this->crawler_manager;
  }

  /**
   * @param \Drupal\prefetcher\PrefetcherCrawlerManager $crawlerManager
   */
  public function setCrawlerManager(PrefetcherCrawlerManager $crawlerManager) {
    $this->crawler_manager = $crawlerManager;
  }

}
