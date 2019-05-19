<?php

namespace Drupal\visualn_drawing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\visualn_drawing\Entity\VisualNDrawingTypeInterface;

/**
 * Provides dynamic permissions for each visualn_drawing type.
 */
class VisualNDrawingPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * VisualNDrawingPermissions constructor.
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
   * Returns an array of visualn_drawing type permissions.
   *
   * @return array
   *   The visualn_drawing type permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function visualnDrawingTypePermissions() {
    $perms = [];
    // Generate visualn_drawing permissions for all visualn_drawing types.
    $drawing_types = $this->entityTypeManager
      ->getStorage('visualn_drawing_type')->loadMultiple();
    foreach ($drawing_types as $type) {
      $perms += $this->buildPermissions($type);
    }
    return $perms;
  }

  /**
   * Returns a list of visualn_drawing permissions for a given visualn_drawing type.
   *
   * @param \Drupal\visualn_drawing\VisualNDrawingTypeInterface $type
   *   The visualn_drawing type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(VisualNDrawingTypeInterface $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "create $type_id visualn drawing" => [
        'title' => $this->t('%type_name: Create new VisualN Drawing', $type_params),
      ],
      "edit own $type_id visualn drawing" => [
        'title' => $this->t('%type_name: Edit own VisualN Drawing', $type_params),
      ],
      "edit any $type_id visualn drawing" => [
        'title' => $this->t('%type_name: Edit any VisualN Drawing', $type_params),
      ],
      "delete own $type_id visualn drawing" => [
        'title' => $this->t('%type_name: Delete own VisualN Drawing', $type_params),
      ],
      "delete any $type_id visualn drawing" => [
        'title' => $this->t('%type_name: Delete any VisualN Drawing', $type_params),
      ],
    ];
  }

}
