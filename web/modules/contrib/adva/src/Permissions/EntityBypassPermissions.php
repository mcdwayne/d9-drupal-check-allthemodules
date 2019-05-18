<?php

namespace Drupal\adva\Permissions;

use Drupal\adva\Plugin\adva\Manager\AccessConsumerManagerInterface;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines EntityBypassPermissions.
 */
class EntityBypassPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Current access consumer manager.
   *
   * @var \Drupal\adva\Plugin\adva\Manager\AccessConsumerManagerInterface
   */
  public $accessConsumerManager;

  /**
   * Creates a new EntityBypassPermissions object.
   *
   * @param \Drupal\adva\Plugin\adva\Manager\AccessConsumerManagerInterface $access_consumer_manager
   *   The current access consumer manager instance.
   */
  public function __construct(AccessConsumerManagerInterface $access_consumer_manager) {
    $this->accessConsumerManager = $access_consumer_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.adva.consumer'));
  }

  /**
   * Get bypass permissions for available entity types controlled by adva.
   *
   * @return array
   *   Permissions array.
   */
  public function permissions() {
    $permissions = [];

    foreach ($this->accessConsumerManager->getConsumers() as $consumer) {
      $entity_type_id = $consumer->getEntityTypeId();
      $context = ['%type' => $entity_type_id];

      $permissions['bypass adva ' . $entity_type_id . ' access'] = [
        'title' => $this->t('Bypass Advanced Access grants for %type', $context),
        'description' => $this->t('Allows bypasing of access checks on %type entities.', $context),
        'restrict access' => TRUE,
      ];
    }

    return $permissions;
  }

}
