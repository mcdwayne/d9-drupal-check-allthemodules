<?php

namespace Drupal\taxonomy_reference_shield;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides methods for handling entity operations.
 */
final class EntityOperations implements ContainerInjectionInterface {

  /**
   * The module's configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The reference handler.
   *
   * @var \Drupal\taxonomy_reference_shield\ReferenceHandlerInterface
   */
  protected $referenceHandler;

  /**
   * The redirect destination core tool.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Builds a new EntityOperations object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\taxonomy_reference_shield\ReferenceHandlerInterface $reference_handler
   *   The module's reference handler tool.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination core tool.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ReferenceHandlerInterface $reference_handler, RedirectDestinationInterface $redirect_destination) {
    $this->config = $config_factory->get('taxonomy_reference_shield.config')->get('enabled');
    $this->referenceHandler = $reference_handler;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('taxonomy_reference_shield.relationship_handler'),
      $container->get('redirect.destination')
    );
  }

  /**
   * Validates access to the delete operation.
   *
   * @see hook_ENTITY_TYPE_access()
   */
  public function onTermAccess(TermInterface $term, $operation, AccountInterface $account) {
    if ($operation == 'delete') {
      if (isset($this->config[$term->bundle()]) && $this->config[$term->bundle()] && $this->referenceHandler->getReferences($term, TRUE)) {
        return AccessResult::forbidden();
      }
    }
    return AccessResult::neutral();
  }

  /**
   * Alters term entity type to add new link template.
   *
   * @see hook_entity_type_alter()
   */
  public function entityOperation(EntityInterface $entity) {
    $operations = [];
    if ($entity->getEntityTypeId() == 'taxonomy_term') {
      $url = $entity->urlInfo('shield_delete');
      $url->mergeOptions(['query' => $this->redirectDestination->getAsArray()]);
      if ($url->access()) {
        $operations['shield-delete'] = [
          'title' => t('Delete'),
          'url' => $url,
          'weight' => 50,
        ];
      }
    }
    return $operations;
  }

}
