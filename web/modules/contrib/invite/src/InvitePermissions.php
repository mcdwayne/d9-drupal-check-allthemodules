<?php

namespace Drupal\invite;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the invite module.
 */
class InvitePermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new InvitePermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Returns an array of invite permissions.
   *
   * @return array
   *   Returns the array with permissions.
   */
  public function permissions() {
    $permissions = [];
    // Generate permissions for each invite type.
    $invite_types = $this->entityTypeManager->getStorage('invite_type')
      ->loadMultiple();
    foreach ($invite_types as $invite_type) {
      $permissions['invite_type_' . $invite_type->getType()] = [
        'title' => $this->t('Create @label invites', ['@label' => $invite_type->label()]),
        'description' => [
          '#prefix' => '<em>',
          '#markup' => $this->t('Warning: This permission could have security implications.'),
          '#suffix' => '</em>',
        ],
      ];
    }
    return $permissions;
  }

}
