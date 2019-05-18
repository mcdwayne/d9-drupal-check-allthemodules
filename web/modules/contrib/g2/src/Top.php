<?php

/**
 * @file
 * Contains G2 Top service.
 */

namespace Drupal\g2;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGenerator;
use Psr\Log\LoggerInterface;

/**
 * Class Top implements the g2.top service.
 */
class Top {
  const STATISTICS_DAY = 'daycount';
  const STATISTICS_TOTAL = 'totalcount';

  const STATISTICS_TYPES = [self::STATISTICS_DAY, self::STATISTICS_TOTAL];

  /**
   * The service availability status.
   *
   * @var bool
   */
  protected $available;

  /**
   * The configuration hash for this service.
   *
   * Keys:
   * - max: the maximum number of entries returned. 0 for unlimited.
   *
   * @var array
   */
  protected $config;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity query service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The link generator service.
   *
   * @var \Drupal\Core\Utility\LinkGenerator
   */
  protected $linkGenerator;

  /**
   * The logger.channel.g2 service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The URL generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory service.
   * @param \Drupal\Core\Utility\LinkGenerator $link_generator
   *   The link generator service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity.query service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module_handler service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.channel.g2 service.
   */
  public function __construct(ConfigFactoryInterface $config, LinkGenerator $link_generator,
    QueryFactory $entity_query, UrlGeneratorInterface $url_generator,
    ModuleHandlerInterface $module_handler, Connection $connection, LoggerInterface $logger) {
    $this->available = $module_handler->moduleExists('statistics');
    $this->database = $connection;
    $this->entityQuery = $entity_query;
    $this->linkGenerator = $link_generator;
    $this->logger = $logger;
    $this->urlGenerator = $url_generator;

    $g2_config = $config->get('g2.settings');
    $this->config = $g2_config->get('service.latest');

  }

  /**
   * Return the top visited entries.
   *
   * @param int $count
   *   The maximum number of entries to return. Limited both by the configured
   *   maximum number of entries and the actual number of entries available.
   * @param string $statistic
   *   The type of statistic by which to order. Must be one of the
   *   self::STATISTICS_* individual statistics.
   *
   * @return array <integer,\Drupal\g2\TopRecord>
   *   A node-by-nid hash, ordered by latest change timestamp.
   */
  public function getEntries($count, $statistic = self::STATISTICS_DAY) {
    if (!$this->available) {
      return [];
    }

    $count_limit = $this->config['max_count'];
    $count = min($count, $count_limit);

    $result = [];
    /* @var \Drupal\g2\TopRecord $record */
    foreach ($this->statisticsTitleList($statistic, $count) as $record) {
      $record->normalize();
      $result[$record->nid] = $record;
    }
    return $result;
  }

  /**
   * Returns the most viewed content of all time or today.
   *
   * @param string $column
   *   The database field to use, one of:
   *   - 'totalcount': Integer that shows the top viewed content of all time.
   *   - 'daycount': Integer that shows the top viewed content for today.
   *   - 'timestamp': Integer that shows only the last viewed node.
   * @param int $count
   *   The number of rows to be returned.
   *
   * @return \Traversable|array
   *   A query result (Statement) containing the node ID, title, user ID that
   *   owns the node, username for the selected node(s), and number of views, or
   *   an empty array if the query could not be executed correctly.
   *
   * @see statistics_title_list()
   */
  protected function statisticsTitleList($column, $count) {
    if (!in_array($column, static::STATISTICS_TYPES)) {
      return [];
    }

    $options = ['fetch' => '\Drupal\g2\TopRecord'];
    $query = $this->database->select('node_field_data', 'n', $options);
    $query->addTag('node_access');
    $query->join('node_counter', 's', 'n.nid = s.nid');
    $query->join('users_field_data', 'u', 'n.uid = u.uid');
    $query->addField('s', $column, 'views');

    /* Query chaining split to work around incorrect type hinting in DBTNG. */

    /* @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $query
      ->fields('n', ['nid', 'title'])
      ->fields('u', ['uid', 'name'])
      ->condition($column, 0, '<>')
      ->condition('n.status', 1)
      ->condition('n.type', G2::NODE_TYPE)
      // @todo This should be actually filtering on the desired node status
      //   field language and just fall back to the default language.
      ->condition('n.default_langcode', 1)
      ->condition('u.default_langcode', 1);

    $query = $query
      ->orderBy($column, 'DESC')
      ->range(0, $count);

    $result = $query->execute();
    if ($result === FALSE) {
      $this->logger->warning('Failed fetching %type statistics.', ['%type' => $column]);
      $result = [];
    }
    return $result;
  }

  /**
   * Return an array of links to entry pages.
   *
   * @param int $count
   *   The maximum number of entries to return. Limited both by the configured
   *   maximum number of entries and the actual number of entries available.
   * @param string $statistic
   *   The type of statistic by which to order. Must be one of the
   *   self::STATISTICS_* individual statistics.
   *
   * @return array <string,\Drupal\Core\GeneratedLink>
   *   A hash of nid to to entry links.
   */
  public function getLinks($count, $statistic = self::STATISTICS_DAY) {
    $result = [];
    if (!$this->available) {
      return $result;
    }

    $options = [
      // So links can be used outside site pages.
      'absolute' => TRUE,
      // To preserve the pre-encoded path.
      'html' => TRUE,
    ];
    $route_name = 'entity.node.canonical';
    /* @var \Drupal\g2\TopRecord $record */
    foreach ($this->getEntries($count, $statistic) as $record) {
      $parameters = ['node' => $record->nid];
      $url = Url::fromRoute($route_name, $parameters, $options);
      $result[] = $this->linkGenerator->generate($record->title, $url);
    }

    return $result;
  }

  /**
   * Is the service available ?
   *
   * @return bool
   *   The service availability status.
   */
  public function isAvailable() {
    return $this->available;
  }

}
