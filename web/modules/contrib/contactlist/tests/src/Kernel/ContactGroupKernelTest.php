<?php

namespace Drupal\Tests\contactlist\Kernel;

use Drupal\contactlist\Entity\ContactGroup;
use Drupal\contactlist\Entity\ContactListEntry;
use Drupal\KernelTests\KernelTestBase;
use Drupal\contactlist\Tests\ContactListTestTrait;

/**
 * Tests basic ContactGroups functionality.
 *
 * @group ContactList
 *
 * @coversDefaultClass \Drupal\contactlist\Entity\ContactGroup
 */
class ContactGroupKernelTest extends KernelTestBase {

  use ContactListTestTrait;

  public static $modules = ['system', 'user', 'telephone', 'contactlist'];

  public function setUp() {
    parent::setUp();
    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');
    $this->installEntitySchema('contact_group');
    $this->installEntitySchema('contactlist_entry');
  }

  /**
   * Tests the contact groups entity interface methods.
   *
   * @covers ::getName
   * @covers ::setName
   * @covers ::getDescription
   * @covers ::setDescription
   * @covers ::getWeight
   * @covers ::setWeight
   * @covers ::getChangedTime
   * @covers ::setChangedTime
   * @covers ::getOwner
   * @covers ::setOwner
   */
  public function testContactGroupInterface() {
    // Create test entities for the user and unrelated to a user.
    $user = $this->randomUser();
    /** @var \Drupal\contactlist\Entity\ContactGroupInterface $group */
    $group = ContactGroup::create();
    $success = $group
      ->setName('group1')
      ->setDescription('my new group')
      ->setWeight(1)
      ->setOwner($user)
      ->save();

    $this->assertEquals(SAVED_NEW, $success);
    $this->assertEquals(REQUEST_TIME, $group->getChangedTime());
    $this->assertEquals(1, $group->getWeight());
    $this->assertEquals('my new group', $group->getDescription());
    $this->assertEquals($user, $group->getOwner());
    $this->assertEquals('group1', $group->getName());

    // Test update.
    $group->setName('group2');
    $group->setDescription('new description');
    $success = $group->save();
    $this->assertEquals($success, SAVED_UPDATED);
    $loaded_group = ContactGroup::load($group->id());
    $this->assertEquals('group2', $loaded_group->getName());
    $this->assertEquals('new description', $loaded_group->getDescription());
    $this->assertEquals(REQUEST_TIME, $loaded_group->getChangedTime());

    // Test delete.
    $groups = ContactGroup::loadMultiple();
    $group2 = reset($groups);
    $this->assertTrue(in_array($group2->id(), array_keys($groups)));
    $group2->delete();
    $this->assertFalse(in_array($group2->id(), array_keys(ContactGroup::loadMultiple())));
  }

  /**
   * @covers ::getContacts
   */
  public function testGetContacts() {
    $user = $this->randomUser();

    $groups = $this->createContactGroups(['group1', 'group2', 'group3'], $user);
    $contact1 = $this->createContact()
      ->setOwner($user)
      ->setGroups($groups);
    $contact1->save();

    $contact2 = $this->createContact()
      ->setOwner($user)
      ->setGroups([$groups[0], $groups[1]]);
    $contact2->save();

    $contact3 = $this->createContact()
      ->setOwner($user)
      ->setGroups([$groups[0]]);
    $contact3->save();

    // Reload contacts.
    list($contact1, $contact2, $contact3) = array_values(ContactListEntry::loadMultiple([
      $contact1->id(),
      $contact2->id(),
      $contact3->id(),
    ]));

    $this->assertCount(3, $groups);
    $this->assertEquals([$contact1, $contact2, $contact3], $groups[0]->getContacts());
    $this->assertEquals([$contact1, $contact2], $groups[1]->getContacts());
    $this->assertEquals([$contact1], $groups[2]->getContacts());

    $contact3->addGroups($this->createContactGroups(['group4'], $user))->save();
    $this->assertCount(2, $contact3->getGroups());
    $this->assertCount(1, $contact3->getGroups()[1]->getContacts());
  }

  /**
   * Confirms that different users' groups are not leaked to each other.
   */
  public function testSeparateUserGroups() {
    // Add another contact that belongs to another user but in a group with same
    // name.
    $user = $this->randomUser();
    $groups = $this->createContactGroups(['group1'], $user);
    $contact = $this->createContact()
      ->setOwner($user)
      ->setGroups($groups);
    $contact->save();
    $contact = ContactListEntry::load($contact->id());

    // Add another contact that belongs to another user but in a group with same
    // name.
    $other_user = $this->randomUser();
    $other_groups = $this->createContactGroups(['group1'], $other_user);
    $other_contact = $this->createContact()
      ->setOwner($other_user)
      ->setGroups($other_groups);
    $other_contact->save();
    $other_contact = ContactListEntry::load($other_contact->id());

    // There are actually two groups called 'group1'.
    $group_storage = $this->container->get('entity_type.manager')->getStorage('contact_group');
    $query = $group_storage->getQuery();
    $ids = $query
      ->accessCheck(FALSE)
      ->condition('name', 'group1')
      ->execute();
    $groups = $group_storage->loadMultiple($ids);
    $this->assertCount(2, $groups);
    $this->assertCount(1, $groups[1]->getContacts());
    $this->assertCount(1, $groups[2]->getContacts());
    $this->assertEquals($contact, $contact->getGroups()[0]->getContacts()[0]);
    $this->assertEquals($other_contact, $other_contact->getGroups()[0]->getContacts()[0]);
    $this->assertNotEquals($other_contact, $contact->getGroups()[0]->getContacts()[0]);
  }

  /**
   * Tests that contacts are removed from groups when groups are deleted.
   */
  public function testContactGroupPreDelete() {
    $user = $this->randomUser();
    $group1 = $this->createContactGroups(['group1'], $user)[0];
    $group2 = $this->createContactGroups(['group2'], $user)[0];

    $contact1 = $this->createContact()
      ->setOwner($user)
      ->setGroups([$group1, $group2]);
    $contact1->save();

    $contact2 = $this->createContact()
      ->setOwner($user)
      ->setGroups([$group1]);
    $contact2->save();

    $this->assertCount(2, $contact1->getGroups());
    $this->assertCount(1, $contact2->getGroups());
    $this->assertCount(2, $group1->getContacts());
    $this->assertCount(1, $group2->getContacts());

    // Delete the first group and confirm that the contacts are removed.
    $group1->delete();
    $contact1 = ContactListEntry::load($contact1->id());
    $contact2 = ContactListEntry::load($contact2->id());
    $this->assertCount(1, $contact1->getGroups());
    $this->assertCount(0, $contact2->getGroups());
    $this->assertCount(0, $group1->getContacts());
    $this->assertCount(1, $group2->getContacts());
    $this->assertEquals([$group2], $contact1->getGroups());

    $group2->delete();
    $contact1 = ContactListEntry::load($contact1->id());
    $this->assertCount(0, $contact1->getGroups());
    $this->assertCount(0, $group1->getContacts());
    $this->assertCount(0, $group2->getContacts());
  }

}
