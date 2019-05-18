<?php

namespace Drupal\quick_code;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class QuickCodePermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;
  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a QuickCodePermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
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
   * Get taxonomy permissions.
   *
   * @return array
   *   Permissions array.
   */
  public function permissions() {
    $permissions = [];
    foreach ($this->entityTypeManager->getStorage('quick_code_type')->loadMultiple() as $type) {
      $permissions += [
        'edit ' . $type->id() . ' quick code' => [
          'title' => $this->t('Edit %type quick code', ['%type' => $type->label()]),
        ],
      ];
      $permissions += [
        'delete ' . $type->id() . ' quick code' => [
          'title' => $this->t('Delete %type quick code', ['%type' => $type->label()]),
        ],
      ];
    }
    return $permissions;
  }

}
