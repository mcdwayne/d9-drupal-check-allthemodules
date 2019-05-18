<?php

namespace Drupal\edit_own_unpublished;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions for Edit Own Unpublished.
 */
class EditOwnUnpublishedPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new EditOwnUnpublishedPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'));
  }

  /**
   * Returns an array of edit own unpublished permissions.
   *
   * @return array
   */
  public function permissions() {
    $permissions = [];
    $node_bundles = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();

    foreach ($node_bundles as $bundle) {
      $permissions['edit own unpublished ' . $bundle->id()] = [
        'title' => $this->t('<a href=":url">@label nodes</a>: Edit own unpublished content', [':url' => $bundle->url(), '@label' => $bundle->label()]),
        'description' => [
          '#prefix' => '<em>',
          '#markup' => $this->t(
            'Note: If loss of edit on node publish is desired, ensure other permission modules don\'t grant it.'
          ),
          '#suffix' => '</em>',
        ],
      ];
    }

    $permissions['edit own unpublished any'] = [
      'title' => $this->t('Any node: Edit own unpublished content of any type'),
      'description' => [
        '#prefix' => '<em>',
        '#markup' => $this->t(
          'Note: If loss of edit on node publish is desired, ensure other permission modules don\'t grant it.'
        ),
        '#suffix' => '</em>',
      ],
    ];

    return $permissions;
  }

}
