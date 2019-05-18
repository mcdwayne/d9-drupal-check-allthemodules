<?php

declare(strict_types = 1);

namespace Drupal\erg;

use Drupal\erg\Guard\GuardInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Defines an entity reference.
 */
final class EntityReference {

  public const RESPONSE_IMMEDIATE = 'immediate';
  public const RESPONSE_DEFER = 'defer';

  private $referee;
  private $fieldName;
  private $referent;
  private $guards = [];
  private $response;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Entity\EntityInterface|string $referee
   *   The referee. Either an entity, or a string in the format
   *   "$entity_type_id:$entity_id".
   * @param string $field_name
   *   The entity reference field name.
   * @param \Drupal\Core\Entity\EntityInterface|string $referent
   *   The referent.
   * @param \Drupal\erg\Guard\GuardInterface[] $guards
   *   The guards.
   * @param string $response
   *   One of the self::RESPONSE_* constants.
   */
  public function __construct(
        $referee,
        string $field_name,
        $referent,
        array $guards,
        string $response = self::RESPONSE_IMMEDIATE
    ) {
    $this->referee = $referee;
    $this->fieldName = $field_name;
    $this->referent = $referent;
    foreach ($guards as $guard) {
      assert($guard instanceof GuardInterface);
    }
    $this->guards = $guards;
    $this->response = $response;
  }

  /**
   * Loads an entity by serialized ID.
   *
   * @param string $id
   *   The serialized ID, which are the entity type and entity IDs, joined by a
   *   colon.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity, or NULL if it could not be found.
   */
  private function loadEntity(string $id): ?EntityInterface {
    [$entity_type_id, $entity_id] = explode(':', $id);
    return \Drupal::entityTypeManager()->getStorage($entity_type_id)->load($entity_id);
  }

  /**
   * Gets the referee.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface|null
   *   The referee, if it can be found.
   */
  public function getReferee(): ?FieldableEntityInterface {
    if (is_string($this->referee)) {
      $this->referee = $this->loadEntity($this->referee) ?: $this->referee;
    }
    if ($this->referee instanceof EntityInterface) {
      return $this->referee;
    }
    return NULL;
  }

  /**
   * Gets the referent.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The referent, if it can be found.
   */
  public function getReferent(): ?EntityInterface {
    if (is_string($this->referent)) {
      $this->referent = $this->loadEntity($this->referent) ?: $this->referent;
    }
    if ($this->referent instanceof EntityInterface) {
      return $this->referent;
    }
    return NULL;
  }

  /**
   * Gets the name of the entity reference field on the referee.
   *
   * @return string
   *   The field name.
   */
  public function getFieldName(): string {
    return $this->fieldName;
  }

  /**
   * Gets the guards for this reference.
   *
   * @return \Drupal\erg\Guard\GuardInterface[]
   *   The guards.
   */
  public function getGuards(): array {
    return $this->guards;
  }

  /**
   * Gets the response type.
   *
   * @return string
   *   One of the self::RESPONSE_* constants.
   */
  public function getResponse(): string {
    return $this->response;
  }

  /**
   * Guards this entity reference.
   *
   * @param string $event
   *   The event to guard the reference for.
   *
   * @throws \Drupal\erg\Guard\GuardExceptionInterface
   *   Thrown if a guard wants to prevent the event from completing.
   */
  public function guard(string $event) {
    foreach ($this->guards as $guard) {
      if ($event === $guard->getEvent()) {
        $guard->guardReference($this);
      }
    }
  }

}
