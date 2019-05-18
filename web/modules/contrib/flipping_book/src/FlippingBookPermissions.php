<?php

namespace Drupal\flipping_book;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flipping_book\Entity\FlippingBookType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the flipping_book module.
 */
class FlippingBookPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new FlippingBookPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Returns an array of private flipping books permissions.
   *
   * @return array
   */
  public function permissions() {
    $permissions = [];

    $storage = $this->entityManager->getStorage('flipping_book_type');
    /** @var FlippingBookType[] $types */
    $types = $storage->loadByProperties([
      'location' => FlippingBookInterface::FLIPPING_BOOK_PRIVATE,
    ]);
    uasort($types, 'Drupal\Core\Config\Entity\ConfigEntityBase::sort');

    foreach ($types as $type) {
      if ($permission = $type->getPermissionName()) {
        $permissions[$permission] = [
          'title' => $this->t('Access "%label" Flipping Books', [
            '%label' => $type->label(),
          ]),
          'description' => [
            '#prefix' => '<em>',
            '#markup' => $this->t('Allow access to flipping books imported into the private folder.'),
            '#suffix' => '</em>'
          ],
        ];
      }
    }
    return $permissions;
  }

}
