<?php

namespace Drupal\real_estate_rets;

use Drupal\real_estate_rets\Entity\RetsConnectionInterface;

/**
 * A query value object.
 */
class RetsQuery implements RetsQueryInterface {

  /**
   * The connection that this query is connected to.
   *
   * @var \Drupal\real_estate_rets\Entity\RetsConnectionInterface
   */
  private $connection;

  /**
   * The query's ID.
   *
   * @var string
   */
  private $id;

  /**
   * The query's label.
   *
   * @var string
   */
  private $label;

  /**
   * The query's weight.
   *
   * @var string
   */
  private $weight;

  /**
   * The query's resource.
   *
   * @var string
   */
  private $resource;

  /**
   * The query's class.
   *
   * @var string
   */
  private $class;

  /**
   * The query's value.
   *
   * @var string
   */
  private $query;

  /**
   * The query's dmql.
   *
   * @var string
   */
  private $dmql;

  /**
   * The query's format.
   *
   * @var string
   */
  private $format;

  /**
   * The query's limit.
   *
   * @var string
   */
  private $limit;

  /**
   * The query's StandardNames.
   *
   * @var string
   */
  private $standardnames;

  /**
   * The query's key_field.
   *
   * @var string
   */
  private $keyField;

  /**
   * The query's entity.
   *
   * @var string
   */
  private $entity;

  /**
   * The query's select.
   *
   * @var string
   */
  private $select;

  /**
   * RetsQuery constructor.
   *
   * @param \Drupal\real_estate_rets\Entity\RetsConnectionInterface $connection
   *   The connection the query is attached to.
   * @param string $id
   *   The query's ID.
   * @param string $label
   *   The query's label.
   * @param string $weight
   *   The query's weight.
   * @param string $resource
   *   The query's resource.
   * @param string $class
   *   The query's class.
   * @param string $query
   *   The query's value.
   * @param string $dmql
   *   The query's dmql.
   * @param string $format
   *   The query's format.
   * @param string $limit
   *   The query's limit.
   * @param string $standardnames
   *   The query's standardnames.
   * @param string $key_field
   *   The query's key_field.
   * @param string $entity
   *   The query's entity.
   * @param string $select
   *   The query's select.
   */
  public function __construct(RetsConnectionInterface $connection, $id, $label, $weight, $resource, $class, $query, $dmql, $format, $limit, $standardnames, $key_field, $entity, $select) {
    $this->connection = $connection;
    $this->id = $id;
    $this->label = $label;
    $this->weight = $weight;
    $this->resource = $resource;
    $this->class = $class;
    $this->query = $query;
    $this->dmql = $dmql;
    $this->format = $format;
    $this->limit = $limit;
    $this->standardnames = $standardnames;
    $this->keyField = $key_field;
    $this->entity = $entity;
    $this->select = $select;
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
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function weight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function resource() {
    return $this->resource;
  }

  /**
   * {@inheritdoc}
   */
  public function class() {
    return $this->class;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->query;
  }

  /**
   * {@inheritdoc}
   */
  public function dmql() {
    return $this->dmql;
  }

  /**
   * {@inheritdoc}
   */
  public function format() {
    return $this->format;
  }

  /**
   * {@inheritdoc}
   */
  public function limit() {
    return $this->limit;
  }

  /**
   * {@inheritdoc}
   */
  public function standardnames() {
    return $this->standardnames;
  }

  /**
   * {@inheritdoc}
   */
  public function keyField() {
    return $this->keyField;
  }

  /**
   * {@inheritdoc}
   */
  public function entity() {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function select() {
    return $this->select;
  }

}
