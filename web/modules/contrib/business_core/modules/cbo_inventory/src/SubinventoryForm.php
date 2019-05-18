<?php

namespace Drupal\cbo_inventory;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the subinventory edit forms.
 */
class SubinventoryForm extends ContentEntityForm {

  /**
   * The inventory manager service.
   *
   * @var \Drupal\cbo_inventory\InventoryManagerInterface
   */
  protected $inventoryManager;

  /**
   * Constructs a BlockContentForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\cbo_inventory\InventoryManagerInterface $inventory_manager
   *   The inventory manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, InventoryManagerInterface $inventory_manager) {
    parent::__construct($entity_manager);
    $this->inventoryManager = $inventory_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('inventory.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function prepareEntity() {
    parent::prepareEntity();

    $entity = $this->entity;
    if ($organization = $this->inventoryManager->currentInventoryOrganization()) {
      $entity->organization->target_id = $organization->id();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $insert = $entity->isNew();
    $entity->save();
    $entity_link = $entity->link($this->t('View'));
    $context = ['%title' => $entity->label(), 'link' => $entity_link];
    $t_args = ['%title' => $entity->link($entity->label())];

    if ($insert) {
      $this->logger('inventory')->notice('Subinventory: added %title.', $context);
      drupal_set_message($this->t('Subinventory %title has been created.', $t_args));
    }
    else {
      $this->logger('inventory')->notice('Subinventory: updated %title.', $context);
      drupal_set_message($this->t('Subinventory %title has been updated.', $t_args));
    }
  }

}
