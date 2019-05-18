<?php

namespace Drupal\entity_delete_op\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\entity_delete_op\DeleteManagerInterface;
use Drupal\Core\Action\Plugin\Action\EntityActionBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_delete_op\EntityDeletableInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the "Delete" action.
 *
 * @Action(
 *   id = "entity:entity_delete_op_delete",
 *   action_label = @Translation("Entity delete op: delete"),
 *   deriver = "Drupal\entity_delete_op\Plugin\Action\Derivative\DeletableEntityActionDeriver"
 * )
 */
class DeleteAction extends EntityActionBase {

  /**
   * The delete manager.
   *
   * @var \Drupal\entity_delete_op\DeleteManagerInterface
   */
  protected $deleteManager;

  /**
   * Creates a new RestoreAction instance.
   *
   * @param array $configuration
   *   The action configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\entity_delete_op\DeleteManagerInterface $delete_manager
   *   The deletion manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, DeleteManagerInterface $delete_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
    $this->deleteManager = $delete_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_delete_op.manager')
    );
  }

  /**
   * Executes the delete action.
   *
   * @param \Drupal\entity_delete_op\EntityDeletableInterface|null $entity
   *   The entity to delete.
   */
  public function execute($entity = NULL) {
    if ($entity instanceof EntityDeletableInterface) {
      $this->deleteManager->delete($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\Core\Entity\EntityInterface $object */
    /** @var \Drupal\Core\Access\AccessResultInterface $access */
    $access = $object->access('update', $account, TRUE);

    if ($account->hasPermission('administer entity_delete_op')) {
      $access->andIf(AccessResult::allowedIfHasPermission($account, 'administer entity_delete_op'));
      return $return_as_object ? $access : $access->isAllowed();
    }

    if (!$access->isAllowed()) {
      return $return_as_object ? $access : $access->isAllowed();
    }

    $permissions = [
      'entity_delete_op delete any ' . $object->getEntityTypeId() . ' entities',
    ];

    if ($object instanceof EntityOwnerInterface) {
      if ($account->id() == $object->getOwnerId()) {
        $permissions[] = 'entity_delete_op delete own ' . $object->getEntityTypeId() . ' entities';
      }
    }

    $access->andIf(AccessResult::allowedIfHasPermissions($account, $permissions, 'OR'));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
