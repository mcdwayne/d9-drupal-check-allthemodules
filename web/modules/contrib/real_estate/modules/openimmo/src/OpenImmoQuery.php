<?php

namespace Drupal\real_estate_openimmo;

use Drupal\real_estate_openimmo\Entity\OpenImmoInterface;

/**
 * A query value object.
 */
class OpenImmoQuery implements OpenImmoQueryInterface {

  /**
   * The source that this query is connected to.
   *
   * @var \Drupal\real_estate_openimmo\Entity\OpenImmoInterface
   */
  private $source;

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
   * OpenImmoQuery constructor.
   *
   * @param \Drupal\real_estate_openimmo\Entity\OpenImmoInterface $source
   *   The source the query is attached to.
   * @param string $id
   *   The query's ID.
   * @param string $label
   *   The query's label.
   * @param string $weight
   *   The query's weight.
   * @param string $key_field
   *   The query's key_field.
   * @param string $entity
   *   The query's entity.
   * @param string $select
   *   The query's select.
   */
  public function __construct(OpenImmoInterface $source, $id, $label, $weight, $key_field, $entity, $select) {
    $this->source = $source;
    $this->id = $id;
    $this->label = $label;
    $this->weight = $weight;
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
