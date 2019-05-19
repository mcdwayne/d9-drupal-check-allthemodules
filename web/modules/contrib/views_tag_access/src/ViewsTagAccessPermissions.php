<?php

namespace Drupal\views_tag_access;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the views tag access module.
 */
class ViewsTagAccessPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ViewsTagAccessPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, ConfigFactoryInterface $config_factory) {
    $this->entityManager = $entity_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Returns an array of filter permissions.
   *
   * @return array
   *   An array of permission definitions.
   */
  public function permissions() {
    $permissions = [];

    // Generate permissions for view tag.
    $tags = $this->configFactory->get('views_tag_access.settings')->get('tags');

    // Warn the administrator that any of them are potentially unsafe.
    foreach ($tags as $tag) {
      $permissions["administer views tagged {$tag}"] = [
        'title' => $this->t('Adminsiter views with the <em>@tag</em> tag', ['@tag' => $tag]),
        'restrict access' => TRUE,
      ];
      $permissions["update views tagged {$tag}"] = [
        'title' => $this->t('Update views with the <em>@tag</em> tag', ['@tag' => $tag]),
        'restrict access' => TRUE,
      ];
      $permissions["duplicate views tagged {$tag}"] = [
        'title' => $this->t('Duplicate views with the <em>@tag</em> tag', ['@tag' => $tag]),
        'restrict access' => TRUE,
      ];
      $permissions["enable views tagged {$tag}"] = [
        'title' => $this->t('Enable views with the <em>@tag</em> tag', ['@tag' => $tag]),
        'restrict access' => TRUE,
      ];
      $permissions["disable views tagged {$tag}"] = [
        'title' => $this->t('Disable views with the <em>@tag</em> tag', ['@tag' => $tag]),
        'restrict access' => TRUE,
      ];
      $permissions["delete views tagged {$tag}"] = [
        'title' => $this->t('Delete views with the <em>@tag</em> tag', ['@tag' => $tag]),
        'restrict access' => TRUE,
      ];
    }

    return $permissions;
  }

}
