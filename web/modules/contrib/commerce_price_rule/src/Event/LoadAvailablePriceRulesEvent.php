<?php

namespace Drupal\commerce_price_rule\Event;

use Drupal\Core\Entity\Query\QueryInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the load available price rules event.
 *
 * The default behavior is to load all price rules and evaluate them one by one,
 * until a rule returns a calculated price for the product variation. Modules may
 * want to customize the query so that only relevant price rules are loaded and
 * evaluated (relevant based on application or moudle-specific logic).
 */
class LoadAvailablePriceRulesEvent extends Event {

  /**
   * The query that loads available price rules.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $query;

  /**
   * Constructs a new LoadAvailablePriceRulesEvent object.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   Sets the query object.
   */
  public function __construct(QueryInterface $query) {
    $this->query = $query;
  }

  /**
   * Get the query object.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   Returns the query.
   */
  public function getQuery() {
    return $this->query;
  }

}
