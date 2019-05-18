<?php

namespace Drupal\block_content_revision_ui;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BlockContentRevisionUiPermissions implements ContainerInjectionInterface {

  /**
   * Block content type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockContentTypeStorage;

  /**
   * BlockContentRevisionUiPermissions constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $blockContentTypeStorage
   *   Block content type storage.
   */
  public function __construct(EntityStorageInterface $blockContentTypeStorage) {
    $this->blockContentTypeStorage = $blockContentTypeStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('block_content_type')
    );
  }

  /**
   * Generate dynamic permissions.
   */
  public function permissions() {
    $permissions = [];

    foreach (array_keys($this->blockContentTypeStorage->loadMultiple()) as $bundle) {
      $permissions['view block_content ' . $bundle . ' revisions'] = [
        'title' => 'view block_content ' . $bundle . ' revisions',
      ];
      $permissions['revert block_content ' . $bundle . ' revisions'] = [
        'title' => 'revert block_content ' . $bundle . ' revisions',
      ];
      $permissions['delete block_content ' . $bundle . ' revisions'] = [
        'title' => 'delete block_content ' . $bundle . ' revisions',
      ];
    }

    return $permissions;
  }

}
