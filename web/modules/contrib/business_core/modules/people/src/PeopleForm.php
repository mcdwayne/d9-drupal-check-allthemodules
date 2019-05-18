<?php

namespace Drupal\people;

use Drupal\cbo\CboEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the people edit forms.
 */
class PeopleForm extends CboEntityForm {

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

}
