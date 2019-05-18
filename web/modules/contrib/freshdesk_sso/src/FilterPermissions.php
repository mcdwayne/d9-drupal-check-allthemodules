<?php

/**
 * @file
 * Contains \Drupal\freshdesk_sso\FilterPermissions.
 */

namespace Drupal\freshdesk_sso;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the filter module.
 */
class FilterPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Constructs a new FilterPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityStorage = $entity_type_manager->getStorage('freshdesk_config');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Returns an array of filter permissions.
   *
   * @return array
   */
  public function permissions() {
    $permissions = [];
    // Generate permissions for each text format. Warn the administrator that any
    // of them are potentially unsafe.
    /** @var \Drupal\freshdesk_sso\Entity\FreshdeskConfig[] $desks */
    $desks = $this->entityStorage->loadMultiple();
    uasort($desks, 'Drupal\Core\Config\Entity\ConfigEntityBase::sort');
    foreach ($desks as $desk) {
      if ($permission = $desk->getPermissionName()) {
        $permissions[$permission] = [
          'title' => $this->t('Access <a href=":url">@label</a> via Freshdesk SSO', [':url' => $desk->domain(), '@label' => $desk->label()]),
          'description' => [
            '#prefix' => '<em>',
            '#markup' => $this->t('Warning: This permission may have security implications.'),
            '#suffix' => '</em>'
          ],
        ];
      }
    }
    return $permissions;
  }

}
