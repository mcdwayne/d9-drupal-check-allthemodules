<?php

namespace Drupal\cloud;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions for Cloud.
 */
class CloudPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs new CloudPermissions object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns an array of cloud config permissions.
   */
  public function configPermissions() {
    $permissions = [];
    $cloud_configs = $this->entityTypeManager->getStorage('cloud_config')->loadMultiple();
    foreach ($cloud_configs as $cloud) {
      $permissions['view ' . $cloud->getCloudContext()] = [
        'title' => $this->t('Access %entity entities', ['%entity' => $cloud->get('name')->value]),
        'description' => $this->t('Allows access to entities in %entity.  Entity permissions such as <em>List AWS Instances</em> and <em>Add AWS Cloud key pair</em> need to be granted in conjunction.  Otherwise, no entities will be shown to the user.', ['%entity' => $cloud->get('name')->value]),
      ];
    }
    return $permissions;
  }

}
