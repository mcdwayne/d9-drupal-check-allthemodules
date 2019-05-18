<?php

/**
 * @file
 * Contains \Drupal\media_sitemap\Controller\MediaSitemapController.
 */

namespace Drupal\media_sitemap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Config\ConfigFactory;

/**
 * Class MediaSitemapController.
 *
 * @package Drupal\media_sitemap\Controller
 */
class MediaSitemapController extends ControllerBase {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $config_factory;
  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database, ConfigFactory $config_factory) {
    $this->database = $database;
    $this->config_factory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('config.factory')
    );
  }

  /**
   * List.
   *
   * @return string
   *   Return Hello string.
   */
  public function listMediaSitemap() {
    $output = '';
    $header = array(
      t('SITEMAP URL'),
      t('CREATED DATE'),
      t('TOTAL LINKS'),
      t('ACTIONS'),
    );
    $rows = array();
    $url = 'public://media_sitemap/image_sitemap.xml';
    $url = file_create_url($url);

    // Rows of table.
    $image_sitemap_created = $this->config('media_sitemap.settings')->get('image_sitemap_created');
    $image_sitemap_number_of_urls = $this->config('media_sitemap.settings')->get('image_sitemap_number_of_urls');
    if (isset($image_sitemap_created) && isset($image_sitemap_number_of_urls)) {
      $rows[] = array(
        $build_link = Link::fromTextAndUrl($url, Url::fromUri($url)),
        date('d-M-Y ', $image_sitemap_created),
        $image_sitemap_number_of_urls,
        Link::fromTextAndUrl(t('Re-generate'), Url::fromRoute('media_sitemap.media_sitemap_batch_controller_generate'))->toString()
      );
    }

    $output = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => Link::fromTextAndUrl(t('Add a new media sitemap'), Url::fromRoute('media_sitemap.media_sitemap_batch_controller_generate'))
    ];
    return $output;
  }
}
