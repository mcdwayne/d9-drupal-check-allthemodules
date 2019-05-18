<?php

namespace Drupal\entity_collector_downloader;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_collector\Entity\EntityCollectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TaxonomyViewsIntegratorPermissions
 *
 * @package Drupal\entity_collector_downloader
 */
class EntityCollectorDownloadAccess implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EntityCollectorDownloadAccess constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Get permissions for Taxonomy Views Integrator.
   *
   * @return array
   *   Permissions array.
   */
  public function permissions() {
    $permissions = [];

    foreach ($this->entityTypeManager->getStorage('entity_collection_type')
               ->loadMultiple() as $entityCollectorType) {
      $permissions += [
        'access entity collection type ' . $entityCollectorType->id() . ' download page' => [
          'title' => $this->t('%entity_collection_type: Access entity collection download page', ['%entity_collection_type' => $entityCollectorType->label()]),
        ],
      ];
    }

    return $permissions;
  }

  /**
   * Validate access to entity collection download page.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionInterface $entity_collection
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   */
  public function access(EntityCollectionInterface $entity_collection, AccountInterface $account) {
    if (!$account->hasPermission('access entity collection type ' . $entity_collection->bundle() . ' download page')) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

}
