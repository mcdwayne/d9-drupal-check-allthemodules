<?php

namespace Drupal\domain_video_sitemap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\domain_video_sitemap\DomainVideoList;
use Symfony\Component\HttpFoundation\Response;
use Drupal\field_collection\Entity\FieldCollectionItem;
use Drupal\Component\Utility\Html;

/**
 * Controller routines for products routes.
 */
class VideoSitemapController extends ControllerBase {

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
   * The domainVideoList object for nodecount.
   *
   * @var \Drupal\domain_video_sitemap\DomainVideoList
   */
  protected $domainVideoList;

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
   * @param \Drupal\domain_video_sitemap\DomainVideoList $domainVideoList
   *   Video list object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $cache, DomainNegotiatorInterface $negotiator, EntityTypeManagerInterface $entitytypemanager, DomainVideoList $domainVideoList) {
    $this->config = $config_factory;
    $this->cache = $cache;
    $this->domainNegotiator = $negotiator;
    $this->entityTypeManager = $entitytypemanager;
    $this->domainVideoList = $domainVideoList;
  }

  /**
   * Create function return static domain loader configuration.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Load the ContainerInterface.
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory'), $container->get('cache.default'), $container->get('domain.negotiator'), $container->get('entity_type.manager'), $container->get('domain_video_sitemap.list')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getvideocontent() {
    $negotiator = $this->domainNegotiator;
    if ($negotiator->getActiveDomain()) {
      $domainID = $negotiator->getActiveDomain()->id();
    }
    $cid = $domainID . '-videositemap';
    $content = '';
    // If nothing loaded from the cache, build it now.
    if (empty($content)) {
      $config = $this->config('video_admin.settings');
      $list_nodes = $this->domainVideoList->domainVideoListNodes();
      $content = '<?xml version="1.0" encoding="UTF-8"?>';
      $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">';
      if ($list_nodes) {
        foreach ($list_nodes as $record) {
          $thumbnail_loc = [];
          // Load the node.
          $node_storage = $this->entityTypeManager()->getStorage('node');
          $node = $node_storage->load($record->nid);
          $type = $node->bundle();
          $node_fields = $this->domainVideoList->domainVideoNodeFields($type);
          $field_types = $config->get('video_sitemap_field_types');
          // Video from files.
          if (isset($field_types['files']) && !empty($field_types['files'])) {
            $node_files = $this->domainVideoList->domainVideoNodeFile();
            foreach ($node_files as $node_file) {
              $nid = $node_file->id;
              if ($nid == $record->nid) {
                $uri = $node_file->uri;
                $thumbnail_loc[] = file_create_url($uri);
              }
            }
          }
          // Video from fields.
          if (isset($field_types['youtube']) && !empty($field_types['youtube'])) {
            foreach ($node_fields as $nfield) {
              if ($nfield->getType() == 'youtube') {
                $field_name = $nfield->getName();
                $thumbnail_loc[] = $node->get($field_name)->getValue()[0]['input'];
              }
              if ($nfield->getType() == 'field_collection') {
                $field_name = $nfield->getName();
                foreach ($node->get($field_name)->getValue() as $item) {
                  $item_val = $item['value'];
                  $field_collection = FieldCollectionItem::load($item_val);
                  foreach ($field_collection->getFieldDefinitions() as $collection) {
                    if ($collection->getType() == 'youtube') {
                      $field_name = $collection->getName();
                      $thumbnail_loc[] = $field_collection->get($field_name)->getValue()[0]['input'];
                    }
                  }
                }
              }
            }
          }
          // Truncate video title to 100 character.
          $title = substr($node->getTitle(), 0, 100);
          // Check if body exist.
          if ($node->get('body')->getValue()) {
            $string = $node->get('body')->getValue()[0]['value'];
            $description_with_tags = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
            $description_full_content = strip_tags($description_with_tags);
            $description = substr($description_full_content, 0, 2048);
          }
          else {
            $description = Html::escape($node->getTitle());
          }
          $options = ['absolute' => TRUE];
          $url_obj = Url::fromRoute('entity.node.canonical', ['node' => $record->nid], $options);
          $url_string = $url_obj->toString();
          if ($thumbnail_loc != NULL) {
            $content .= '<url>';
            $content .= '<loc>' . $url_string . '</loc>';
            foreach ($thumbnail_loc as $thumb_loc) {
              $content .= '<video:video>';
              $content .= '<video:thumbnail_loc>test:' . $thumb_loc . '</video:thumbnail_loc>';
              $content .= '<video:title>' . Html::escape($title) . '</video:title>';
              $content .= '<video:description>' . Html::escape($description) . '</video:description>';
              $content .= '<video:player_loc allow_embed="yes">' . $thumb_loc . '</video:player_loc>';
              $content .= '</video:video>';
            }
            $content .= '</url>';
          }
        }
      }
      $content .= '</urlset>';
      $timeout = time() + (intval($config->get('video_cache_timeout') != '' ? $config->get('video_cache_timeout') : '15') * 60);
      $this->cache->set($cid, $content, $timeout);
    }
    $response = new Response($content, Response::HTTP_OK, ['content-type' => 'application/xml']);
    return $response;
  }

}
