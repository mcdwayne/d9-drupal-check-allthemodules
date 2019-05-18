<?php

namespace Drupal\people;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * People manager contains common functions to manage people.
 */
class PeopleManager implements PeopleManagerInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Construct the PeopleManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function currentCompany() {
    $user_storage = $this->entityTypeManager->getStorage('user');
    if ($user = $user_storage->load($this->currentUser->id())) {
      if ($people = $user->people->entity) {
        $people_storage = $this->entityTypeManager->getStorage('people');
        return $people_storage->getCompany($people);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function currentOrganization() {
    $user_storage = $this->entityTypeManager->getStorage('user');
    if ($user = $user_storage->load($this->currentUser->id())) {
      if ($people = $user->people->entity) {
        return $people->organization->entity;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function currentPeople() {
    $user_storage = $this->entityTypeManager->getStorage('user');
    if ($user = $user_storage->load($this->currentUser->id())) {
      return $user->people->entity;
    }
  }

}
