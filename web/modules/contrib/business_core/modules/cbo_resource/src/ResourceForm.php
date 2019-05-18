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
class ResourceForm extends ContentEntityForm {

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
    $resource = $this->entity;
    $insert = $resource->isNew();
    $resource->save();
    $resource_link = $resource->link($this->t('View'));
    $context = ['%title' => $resource->label(), 'link' => $resource_link];
    $t_args = ['%title' => $resource->link($resource->label())];

    if ($insert) {
      $this->logger('resource')->notice('Resource: added %title.', $context);
      drupal_set_message($this->t('Resource %title has been created.', $t_args));
    }
    else {
      $this->logger('resource')->notice('Resource: updated %title.', $context);
      drupal_set_message($this->t('Resource %title has been updated.', $t_args));
    }
  }

}
