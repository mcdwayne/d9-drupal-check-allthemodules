<?php

namespace Drupal\cbo_resource;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\people\PeopleManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the resource edit forms.
 */
class ResourceListForm extends ContentEntityForm {

  /**
   * The people manager service.
   *
   * @var \Drupal\people\PeopleManagerInterface
   */
  protected $peopleManager;

  /**
   * Constructs a BlockContentForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\people\PeopleManagerInterface $people_manager
   *   The people manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, PeopleManagerInterface $people_manager) {
    parent::__construct($entity_manager);
    $this->peopleManager = $people_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('people.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function prepareEntity() {
    parent::prepareEntity();

    $entity = $this->entity;
    if ($organization = $this->peopleManager->currentOrganization()) {
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
      $this->logger('resource_list')->notice('Resource list: added %title.', $context);
      drupal_set_message($this->t('Resource list %title has been created.', $t_args));
    }
    else {
      $this->logger('resource_list')->notice('Resource list: updated %title.', $context);
      drupal_set_message($this->t('Resource list %title has been updated.', $t_args));
    }
  }

}
