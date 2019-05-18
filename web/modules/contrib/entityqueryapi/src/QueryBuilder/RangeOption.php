<?php

namespace Drupal\entityqueryapi\QueryBuilder;

class RangeOption implements QueryOptionInterface {

  /**
   * A unique key.
   *
   * @var string
   */
  protected $id;

  /**
   * Integer reprenting the result offset.
   *
   * @var int
   */
  protected $start;

  /**
   * Integer reprenting the number of results to return.
   *
   * @var int
   */
  protected $length;

  public function __construct($id, $start = NULL, $length = NULL) {
    $this->id = $id;
    $this->start = $start;
    $this->length = $length;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function apply($query) {
    return $query->range($this->start, $this->length);
  }

}
