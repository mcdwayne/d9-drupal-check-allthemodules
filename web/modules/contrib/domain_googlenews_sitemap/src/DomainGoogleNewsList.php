<?php

namespace Drupal\domain_googlenews;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Database\Connection;

/**
 * Provides class for domain_googlenews.list service.
 */
class DomainGoogleNewsList {

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
    $this->config = $config_factory->get('googlenews_admin.settings');
    $this->domainNegotiator = $domain_negotiator;
    $this->database = $database;
  }

  /**
   * Get a list of all nodes to be output in the Google News sitemap.
   */
  public function domainGooglenewsListNodes() {
    $negotiator = $this->domainNegotiator;
    if (!$negotiator->getActiveDomain()) {
      return ['error' => TRUE];
    }
    else {
      $current_domain = $negotiator->getActiveDomain()->id();
      $time = REQUEST_TIME - intval($this->config->get('googlenews_content_hours') != '' ? $this->config->get('googlenews_content_hours') : '48') * 3600;
      $node_types = node_type_get_names();
      $type = $this->config->get('googlenews_node_types') != '' ? $this->config->get('googlenews_node_types') : array_keys($node_types);
      $db = $this->database;
      $query = $db->select('node_field_data', 'n');
      $query->fields('n', ['nid']);
      $query->leftJoin('node__field_domain_access', 'd', 'n.nid=d.entity_id');
      $query->condition('n.status', '1');
      $query->condition('n.type', $type, 'IN');
      $query->condition('n.created', $time, '>=');
      $query->condition('d.field_domain_access_target_id', $current_domain, '=');
      $query->orderBy('n.created', 'DESC');
      $query->range(0, 50000);
      return $query->execute()->fetchAll();
    }
  }

}
