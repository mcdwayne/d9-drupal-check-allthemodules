<?php

namespace Drupal\domain_video_sitemap;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Database\Connection;

/**
 * Custom class.
 */
class DomainVideoList {

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The DomainNegotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * The DomainNegotiator.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;
  /**
   * Implements __construct().
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\domain\DomainNegotiatorInterface $domain_negotiator
   *   Domain negotiator object.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DomainNegotiatorInterface $domain_negotiator, Connection $database) {
    $this->config = $config_factory->get('video_admin.settings');
    $this->domainNegotiator = $domain_negotiator;
    $this->database = $database;
  }

  /**
   * Get a list of all nodes to be output in the Video News sitemap.
   */
  public function domainVideoListNodes() {
    $negotiator = $this->domainNegotiator;
    if (!$negotiator->getActiveDomain()) {
      return ['error' => TRUE];
    }
    else {
      $current_domain = $negotiator->getActiveDomain()->id();
      $node_types = node_type_get_names();
      $type = $this->config->get('video_node_types') != '' ? $this->config->get('video_node_types') : array_keys($node_types);
      $db = $this->database;
      $query = $db->select('node_field_data', 'n');
      $query->fields('n', ['nid']);
      $query->leftJoin('node__field_domain_access', 'd', 'n.nid=d.entity_id');
      $query->condition('n.status', '1');
      $query->condition('n.type', $type, 'IN');
      $query->condition('d.field_domain_access_target_id', $current_domain, '=');
      $query->orderBy('n.created', 'DESC');
      $query->range(0, 50000);
      return $query->execute()->fetchAll();
    }
  }

  /**
   * Create function return fields.
   *
   * @return array
   *   An array of fields used in content type.
   */
  public function domainVideoNodeFields($bundle) {
    $entity_type = 'node';
    $entityManager = \Drupal::service('entity_field.manager');
    // https://drupal.stackexchange.com/questions/199384/whats-the-best-practice-to-show-a-nodes-fields-in-different-regions/200201.
    $fields = $entityManager->getFieldDefinitions($entity_type, $bundle);
    // $this->entityFieldManager->getFieldDefinitions('node', $node_type).
    return $fields;
  }

  /**
   * Create function return files.
   *
   * @return array
   *   An array of files of type video uploaded.
   */
  public function domainVideoNodeFile() {
    $mine_types = $this->config->get('video_sitemap_exclude_mime_types');
    $node_types = node_type_get_names();
    $type = $this->config->get('video_node_types') != '' ? $this->config->get('video_node_types') : array_keys($node_types);
    $db = $this->database;
    $query = $db->select('file_managed', 'fm');
    $query->innerJoin('file_usage', 'fu', 'fm.fid = fu.fid');
    $query->leftJoin('node_field_data', 'n', 'fu.id = n.nid');
    $query->fields('fm', ['fid', 'filename', 'uri', 'filemime']);
    $query->fields('fu', ['id']);
    $query->condition('n.type', $type, 'IN');
    $query->condition('fm.filemime', "%video%", 'LIKE');
    $query->condition('fm.filemime', $mine_types, 'NOT IN');
    $files = $query->execute()->fetchAll();
    return $files;
  }

  /**
   * Create function return mine_types_array.
   *
   * @return array
   *   An array of minetype of video.
   */
  public function domainVideoMimeTypes() {
    $mine_types_array = [];
    $db = $this->database;
    $query = $db->select('file_managed', 'fm');
    $query->fields('fm', ['filemime']);
    $query->condition('fm.filemime', "%video%", 'LIKE');
    $mine_types = $query->distinct()->execute()->fetchAll();
    foreach ($mine_types as $mine_type) {
      $mine_types_array[$mine_type->filemime] = $mine_type->filemime;
    }
    return $mine_types_array;
  }

}
