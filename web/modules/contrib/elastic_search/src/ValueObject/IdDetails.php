<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 23/12/16
 * Time: 16:05
 */

namespace Drupal\elastic_search\ValueObject;

use twhiston\twLib\Immutable\Immutable;

/**
 * Class IdDetails
 *
 * Immutable value object describing the id, bundle and entity,
 * this is used for mapping field form building
 *
 * @package Drupal\elastic_search\ValueObject
 */
final class IdDetails extends Immutable {

  /**
   * Entityid used as the machine name of the FieldableEntityMap
   *
   * @var string
   */
  private $id;

  /**
   * Entity Type
   *
   * @var string
   */
  private $entity;

  /**
   * Bundle name
   *
   * @var string
   */
  private $bundle;

  /**
   * IdDetails constructor.
   *
   * @param string $entity
   * @param string $bundle
   */
  public function __construct(string $entity, string $bundle) {

    $this->entity = $entity;
    $this->bundle = $bundle;
    if (!empty($this->bundle)) {
      $this->id = "{$entity}__{$bundle}";
    } else {
      $this->id = $entity;
    }
    parent::__construct();
  }

  /**
   * @return string
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * @return string
   */
  public function getEntity(): string {
    return $this->entity;
  }

  /**
   * @return string
   */
  public function getBundle(): string {
    return $this->bundle;
  }

}