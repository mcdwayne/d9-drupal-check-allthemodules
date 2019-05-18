<?php

namespace Drupal\message_thread;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class \Drupal\message_thread\MessageThreadPermissions.
 */
class MessageThreadPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a TaxonomyViewsIntegratorPermissions instance.
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
   * Get permissions for Message Thread .
   *
   * @return array
   *   Permissions array.
   */
  public function permissions() {
    $permissions = [];

    foreach ($this->entityManager->getStorage('message_thread_template')->loadMultiple() as $template) {
      $permissions += [
        'create and receive ' . $template->id() . ' message threads' => [
          'title' => $this->t('Able to participate in %thread threads', ['%thread' => $template->label()]),
        ],
        'view own ' . $template->id() . ' message thread tab' => [
          'title' => $this->t('View own %thread tab', ['%thread' => $template->label()]),
        ],
      ];
    }

    return $permissions;
  }

}
