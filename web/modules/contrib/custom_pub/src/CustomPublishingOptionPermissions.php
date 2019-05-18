<?php

/**
 * @file
 * Contains \Drupal\custom_pub\CustomPublishingOptionPermissions.
 */

namespace Drupal\custom_pub;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CustomPublishingOptionPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a CustomPublishingOptionPermissions instance.
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
   * Get permissions for Custom Publishing Options.
   *
   * @return array
   *   Permissions array.
   */
  public function permissions() {
    $permissions = [];

    foreach ($this->entityManager->getStorage('custom_publishing_option')->loadMultiple() as $machine_name => $publish_option) {
      $permissions += [
        'can set node publish state to ' . $publish_option->id() => [
          'title' => $this->t('Can set node publish state to %type.', array('%type' => $publish_option->label())),
        ]
      ];
    }

    return $permissions;
  }
}