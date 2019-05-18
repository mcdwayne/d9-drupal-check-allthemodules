<?php

/**
 * @file
 * Contains \Drupal\media_sitemap\Controller\MediaSitemapBatchController.
 */

namespace Drupal\media_sitemap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Driver\mysql\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\media_sitemap\MediaSitemapGenerator;

/**
 * Class MediaSitemapBatchController.
 *
 * @package Drupal\media_sitemap\Controller
 */
class MediaSitemapBatchController extends ControllerBase {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;
  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Generate.
   *
   * @return string
   *   Return Hello string.
   */
  public function generate() {
    $batch = array(
      'title' => t('Processing Media files into media sitemap file'),
      'operations' => array(
        array('\Drupal\media_sitemap\MediaSitemapGenerator::generateSitemap', array()),
      ),
      'finished' => 'MediaSitemapGenerator::sitemapGenerateFinished'
    );

    batch_set($batch);
    return batch_process('admin/config/search/media_sitemap');
  }
}
