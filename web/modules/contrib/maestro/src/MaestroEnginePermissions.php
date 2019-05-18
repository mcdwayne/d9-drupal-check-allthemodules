<?php

namespace Drupal\maestro;


use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the maestro module.
 */
class MaestroEnginePermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new MaestroEnginePermissions instance.
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
   * Returns an array of maestro template permissions.
   *
   * @return array
   */
  public function permissions() {
    $permissions = [];

    $templates = $this->entityManager->getStorage('maestro_template')->loadMultiple();
    uasort($templates, 'Drupal\Core\Config\Entity\ConfigEntityBase::sort');
    foreach ($templates as $template) {
      if ($permission = $template->id) {
        $permissions['start template ' . $permission] = [
            'title' => $this->t('Put the @label template into production.', ['@label' => $template->label()]),
            'description' => [
                '#prefix' => '<em>',
                '#markup' => $this->t('Only validated templates can be put into production.'),
                '#suffix' => '</em>'
            ],
        ];
      }
    }
    return $permissions;
  }

}
