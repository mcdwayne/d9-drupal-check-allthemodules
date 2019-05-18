<?php

/**
 * @file
 * Contains G2 Latest service.
 */

namespace Drupal\g2;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGenerator;
use Drupal\node\Entity\Node;

/**
 * Class Latest implements the g2.latest service.
 */
class Latest {
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
   * The link generator service.
   *
   * @var \Drupal\Core\Utility\LinkGenerator
   */
  protected $linkGenerator;

  /**
   * The entity query service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

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
   */
  public function __construct(ConfigFactoryInterface $config, LinkGenerator $link_generator,
    QueryFactory $entity_query, UrlGeneratorInterface $url_generator) {
    $this->linkGenerator = $link_generator;
    $this->entityQuery = $entity_query;
    $this->urlGenerator = $url_generator;

    $g2_config = $config->get('g2.settings');
    $this->config = $g2_config->get('service.latest');
  }

  /**
   * Return the latest updated entries.
   *
   * @param int $count
   *   The maximum number of entries to return. Limited both by the configured
   *   maximum number of entries and the actual number of entries available.
   *
   * @return array<integer,\Drupal\node\NodeInterface>
   *   A node-by-nid hash, ordered by latest change timestamp.
   */
  public function getEntries($count) {
    $count_limit = $this->config['max_count'];
    $count = min($count, $count_limit);

    $query = $this->entityQuery->get('node')
      ->condition('type', G2::NODE_TYPE)
      ->sort('changed', 'DESC')
      ->range(0, $count);
    $ids = $query->execute();
    $result = Node::loadMultiple($ids);
    return $result;
  }

  /**
   * Return an array of links to entry pages.
   *
   * @param int $count
   *   The maximum number of entries to return. Limited both by the configured
   *   maximum number of entries and the actual number of entries available.
   *
   * @return array <string,\Drupal\Core\GeneratedLink>
   *   A hash of nid to to entry links.
   */
  public function getLinks($count) {
    $result = [];
    $options = [
      // So links can be used outside site pages.
      'absolute' => TRUE,
      // To preserve the pre-encoded path.
      'html'     => TRUE,
    ];
    $route_name = 'entity.node.canonical';

    /** @var \Drupal\node\NodeInterface $node */
    foreach ($this->getEntries($count) as $node) {
      $parameters = ['node' => $node->id()];
      $url = Url::fromRoute($route_name, $parameters, $options);
      $result[] = $this->linkGenerator->generate($node->label(), $url);
    }

    return $result;
  }

}
