<?php

namespace Drupal\death_link\Service;

use Drupal\Core\Entity\Query\QueryFactory;

/**
 * RedirectService.
 *
 * @package Drupal\death_link
 */
class RedirectService {

  /**
   * The query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(QueryFactory $queryFactory) {
    $this->queryFactory = $queryFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function getMatchingRedirect($uri) {
    $matches = $this->queryFactory->get('death_link')
      ->condition('fromUri', $uri)
      ->condition('status', 1)
      ->execute();
    return !empty($matches) ? $matches : NULL;
  }

}
