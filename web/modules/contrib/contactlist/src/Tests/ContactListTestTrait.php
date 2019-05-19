<?php

namespace Drupal\contactlist\Tests;

use Drupal\contactlist\Entity\ContactGroup;
use Drupal\contactlist\Entity\ContactListEntry;
use Drupal\user\UserInterface;

trait ContactListTestTrait {

  /**
   * Helper method to create a user with a random name.
   *
   * @return \Drupal\user\UserInterface
   */
  protected function randomUser() {
    $user_storage = $this->container
      ->get('entity_type.manager')
      ->getStorage('user');
    $user = $user_storage->create([
        'name' => $this->randomMachineName()
      ]);
    $user->save();
    return $user_storage->load($user->id());
  }

  /**
   * Helper method to create contact entries.
   *
   * @param array $defaults
   *   Initial values to be given to the contact list entry.
   *
   * @return \Drupal\contactlist\Entity\ContactListEntryInterface
   */
  protected function createContact($defaults = []) {
    return $this->container
      ->get('entity_type.manager')
      ->getStorage('contactlist_entry')
      ->create($defaults);
  }

  /**
   * Helper method to create contact groups.
   *
   * @param array $group_names
   *   The names of the contact groups that are to be created.
   * @param \Drupal\user\UserInterface $user
   *   The owner to be assigned to the group.
   *
   * @return \Drupal\contactlist\Entity\ContactGroupInterface[]
   *   An array of the contact groups corresponding to the names.
   */
  protected function createContactGroups(array $group_names, UserInterface $user) {
    $group_storage = $this->container->get('entity_type.manager')->getStorage('contact_group');
    $ids = [];
    foreach ($group_names as $group_name) {
      $group = $group_storage->create([
        'name' => $group_name,
        'label' => $group_name,
      ]);
      $group->setOwner($user)->save();
      $ids[] = $group->id();
    }
    return array_values($group_storage->loadMultiple($ids));
  }

  /**
   * Helper method to load contact groups based on group names
   *
   * @param array $group_names
   *
   * @return \Drupal\contactlist\Entity\ContactGroupInterface[]
   *   An array of the contact groups corresponding to the names.
   */
  protected function getContactGroups(array $group_names) {
    $group_storage = $this->container->get('entity_type.manager')->getStorage('contact_group');
    $loaded = [];
    foreach ($group_names as $name) {
      $loaded = array_merge($loaded, $group_storage->loadByProperties(['name' => $name]));
    }
    return $loaded;
  }

}
