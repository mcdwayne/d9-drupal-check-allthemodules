<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 01.06.17
 * Time: 15:47
 */

namespace Drupal\elastic_search\ValueObject;

use Drupal\Core\Entity\EntityInterface;
use twhiston\twLib\Immutable\Immutable;

/**
 * Class QueueItem
 *
 * @package Drupal\elastic_search\ValueObject
 */
class QueueItem extends Immutable {

  /**
   * @var string
   */
  private $id;

  /**
   * @var string
   */
  private $uuid;

  /**
   * @var string
   */
  private $entityType;

  /**
   * @var string
   */
  private $language;

  /**
   * @var string
   */
  private $bundle;

  /**
   * QueueItem constructor.
   *
   * @param string $id
   * @param string $uuid
   * @param string $entityType
   * @param string $language
   */
  public function __construct(string $id, string $uuid, string $entityType, string $language, string $bundle) {

    $this->id = $id;
    $this->uuid = $uuid;
    $this->entityType = $entityType;
    $this->language = $language;
    $this->bundle = $bundle;

    parent::__construct();
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return \Drupal\elastic_search\ValueObject\QueueItem
   */
  public static function NewFromEntity(EntityInterface $entity): QueueItem {
    return new self($entity->id(), $entity->uuid(), $entity->getEntityTypeId(), $entity->language()->getId(), $entity->bundle());
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
  public function getUuid(): string {
    return $this->uuid;
  }

  /**
   * @return string
   */
  public function getEntityType(): string {
    return $this->entityType;
  }

  /**
   * @return string
   */
  public function getLanguage(): string {
    return $this->language;
  }

  /**
   * @return string
   */
  public function getBundle(): string {
    return $this->bundle;
  }

}