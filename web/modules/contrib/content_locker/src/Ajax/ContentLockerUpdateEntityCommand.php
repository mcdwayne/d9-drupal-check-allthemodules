<?php

namespace Drupal\content_locker\Ajax;

use Drupal\Core\Ajax\CommandInterface;
use Drupal\Core\Ajax\CommandWithAttachedAssetsInterface;
use Drupal\Core\Ajax\CommandWithAttachedAssetsTrait;

/**
 * AJAX command for updating entity.
 *
 * @ingroup ajax
 *
 * @see \Drupal\Core\Ajax\InsertCommand
 */
class ContentLockerUpdateEntityCommand implements CommandInterface, CommandWithAttachedAssetsInterface {

  use CommandWithAttachedAssetsTrait;

  /**
   * Locker type.
   *
   * @var string
   */
  protected $lockerType;

  /**
   * A entity id.
   *
   * @var string
   */
  protected $entityId;

  /**
   * A entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The render array for the entity.
   *
   * @var array
   */
  protected $content;

  /**
   * Constructs an ContentLockerUpdateEntityCommand object.
   *
   * @param object $entity
   *   An entity.
   * @param string $lockerType
   *   Type of plugin.
   * @param array $content
   *   The render array with the content for the node.
   */
  public function __construct($entity, $lockerType, array $content) {
    $this->entityId = $entity->id();
    $this->entityType = $entity->getEntityTypeId();
    $this->lockerType = $lockerType;
    $this->content = $content;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'contentLockerUpdateEntity',
      'lockerType' => $this->lockerType,
      'entityType' => $this->entityType,
      'entityId' => $this->entityId,
      'data' => $this->getRenderedContent(),
    ];
  }

}
