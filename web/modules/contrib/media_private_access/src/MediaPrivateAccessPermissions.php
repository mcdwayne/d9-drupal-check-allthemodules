<?php

namespace Drupal\media_private_access;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media\MediaTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides some additional dynamic permissions for each media type.
 */
class MediaPrivateAccessPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * MediaPermissions constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Returns an array of additional media type permissions.
   *
   * @return array
   *   The media type permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   * @see \Drupal\media\MediaPermissions::mediaTypePermissions()
   */
  public function mediaTypePermissions() {
    $perms = [];
    // Generate additional media permissions for all media types.
    $media_types = $this->entityTypeManager
      ->getStorage('media_type')->loadMultiple();
    /** @var \Drupal\media\MediaTypeInterface $type */
    foreach ($media_types as $type) {
      $perms += $this->buildPermissions($type);
    }
    return $perms;
  }

  /**
   * Returns a list of media permissions for a given media type.
   *
   * @param \Drupal\media\MediaTypeInterface $type
   *   The media type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(MediaTypeInterface $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "view $type_id media" => [
        'title' => $this->t('%type_name: View media', $type_params),
      ],
    ];
  }

}
