<?php

namespace Drupal\entityqueryapi\QueryBuilder;

class SortOption implements QueryOptionInterface {

  /**
   * A unique key.
   *
   * @var string
   */
  protected $id;

  /**
   * The field by which to sort.
   *
   * @var string
   */
  protected $field;

  /**
   * The direction of the sort.
   *
   * @var string
   */
  protected $direction;

  /**
   * The langcode for the sort.
   *
   * @var string
   */
  protected $langcode;

  public function __construct($id, $field, $direction = 'ASC', $langcode = NULL) {
    $this->id = $id;
    $this->field = $field;
    $this->direction = $direction;
    $this->langcode = $langcode;
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
    return $query->sort($this->field, $this->direction);
  }

}
