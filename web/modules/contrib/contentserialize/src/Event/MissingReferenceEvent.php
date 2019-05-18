<?php

namespace Drupal\contentserialize\Event;

/**
 * An event that allows access to a shared context.
 *
 * @see \Drupal\contentserialize\Importer::import()
 */
class MissingReferenceEvent extends ContextEvent {

  /**
   * The entity type ID of the entity holding the reference.
   *
   * @var string
   */
  protected $type;

  /**
   * The UUID of the entity holding the reference.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The entity type ID of the target (referenced) entity.
   *
   * @var string
   */
  protected $targetType;

  /**
   * The UUID of the target (referenced) entity.
   *
   * @var string
   */
  protected $targetUuid;

  /**
   * The callback to fix the referencing entity once the referenced one exists.
   *
   * @var callable
   */
  protected $entityFixCallback;

  /**
   * Create a missing reference event.
   *
   * @param string $type
   *   The entity type ID of the entity holding the reference.
   * @param string $uuid
   *   The UUID of the entity holding the reference.
   * @param string $target_type
   *   The entity type ID of the target (referenced) entity.
   * @param string $target_uuid
   *   The UUID of the target (referenced) entity.
   * @param callable $entity_fix_callback
   *   The callback to fix the referencing entity.
   * @param array $context
   *   (optional) The shared serialization context.
   */
  public function __construct($type, $uuid, $target_type, $target_uuid, callable $entity_fix_callback, array $context = []) {
    parent::__construct($context);
    $this->type = $type;
    $this->uuid = $uuid;
    $this->targetType = $target_type;
    $this->targetUuid = $target_uuid;
    $this->entityFixCallback = $entity_fix_callback;
  }

  /**
   * Return the entity type ID of the entity holding the reference.
   *
   * @return string
   */
  public function getEntityType() {
    return $this->type;
  }

  /**
   * Return the UUID of the entity holding the reference.
   *
   * @return string
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * Return the entity type ID of the target (referenced) entity.
   *
   * @return string
   */
  public function getTargetEntityType() {
    return $this->targetType;
  }

  /**
   * Return the UUID of the target (referenced) entity.
   *
   * @return string
   */
  public function getTargetUuid() {
    return $this->targetUuid;
  }

  /**
   * Return the callback to fix the referencing entity.
   *
   * @return callable
   */
  public function getCallback() {
    return $this->entityFixCallback;
  }

}