<?php

namespace Drupal\Tests\contactlist\Kernel;

use Drupal\contactlist\Entity\ContactListEntry;
use Drupal\KernelTests\KernelTestBase;
use Drupal\contactlist\Tests\ContactListTestTrait;

/**
 * Tests basic ContactListEntry functionality.
 *
 * @group ContactListEntry
 *
 * @coversDefaultClass \Drupal\contactlist\Entity\ContactListEntry
 */
class ContactListKernelTest extends KernelTestBase {

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
   * Tests ContactListEntry CRUD implementation via Entity API
   */
  public function testCrud() {
    $user1 = $this->randomUser();
    // Create test entities for the user1 and unrelated to a user.
    $defaults = [
      'uid' => $user1->id(),
      'cvid' => 0,
      'created' => time(),
      'changed' => time(),
    ];

    // Test create.
    // Create five new test contact list entries for the user
    for ($i = 0; $i < 5; $i++) {
      $contact = $this->createContact($defaults);
      $contact->save();
    }

    /** @var \Drupal\contactlist\Entity\ContactListEntry[] $contacts */
    $contacts = ContactListEntry::loadMultiple();

    $this->assertCount(5, $contacts);
    foreach ($contacts as $key => $contact) {
      $this->assertEquals($key, $contact->id());
    }

    // Test update.
    $contact = reset($contacts);
    $contact->set('name', 'my_name');
    $contact->set('email', 'my_email');
    $success = $contact->save();
    $this->assertEquals($success, SAVED_UPDATED);
    $loaded_contact = ContactListEntry::load($contact->id());
    $this->assertEquals('my_name', $loaded_contact->get('name')->value, 'ContactListEntry update.');
    $this->assertEquals('my_email', $loaded_contact->get('email')->value, 'ContactListEntry update.');
    $this->assertEquals(REQUEST_TIME, $loaded_contact->get('changed')->value, 'ContactListEntry update.');

    // Test delete.
    $contacts = ContactListEntry::loadMultiple();
    $contact = reset($contacts);
    $ctid2 = $contact->id();
    $contact->delete();
    $keys = array_keys(ContactListEntry::loadMultiple());
    $this->assertFalse(in_array($ctid2, $keys), 'ContactListEntry delete.');
  }

  /**
   * Tests the interface methods.
   *
   * @covers ::getContactName
   * @covers ::setContactName
   * @covers ::getEmail
   * @covers ::setEmail
   * @covers ::getPhoneNumber
   * @covers ::setPhoneNumber
   * @covers ::getCreatedTime
   * @covers ::setCreatedTime
   * @covers ::getOwner
   * @covers ::setOwner
   */
  public function testContactListEntryInterface() {
    $user = $this->randomUser();
    $contact = $this->createContact()
      ->setContactName('my_name')
      ->setEmail('email@example.com')
      ->setPhoneNumber('234567890')
      ->setOwner($user);
    $contact->save();

    $this->assertEquals($user->id(), $contact->getOwner()->id());
    $this->assertEquals('my_name', $contact->getContactName());
    $this->assertEquals($_SERVER['REQUEST_TIME'], $contact->getCreatedTime());
    $this->assertEquals('234567890', $contact->getPhoneNumber());
  }

  /**
   * @covers ::setGroups
   * @covers ::getGroups
   * @covers ::addGroups
   * @covers ::removeGroups
   */
  public function testContactGroups() {
    $user = $this->randomUser();
    $this->container->get('account_switcher')->switchTo($user);
    $contact = $this->createContact();
    $groups = $this->createContactGroups(['group1', 'group2', 'group3'], $user);
    $contact
      ->setOwner($user)
      ->setGroups($groups)
      ->save();
    $this->assertEquals($groups, $contact->getGroups());

    $contact
      ->addGroups($this->createContactGroups(['group4', 'group5', 'group6'], $user));
    $this->assertEquals('group4', $contact->getGroups()[3]->getName());
    $this->assertEquals('group5', $contact->getGroups()[4]->getName());
    $this->assertEquals('group6', $contact->getGroups()[5]->getName());

    $contact
      ->removeGroups($this->getContactGroups(['group1', 'group3', 'group6']));
    $this->assertCount(3, $contact->getGroups());
    $this->assertEquals('group2', $contact->getGroups()[0]->getName());
    $this->assertEquals('group4', $contact->getGroups()[1]->getName());
    $this->assertEquals('group5', $contact->getGroups()[2]->getName());
  }

  /*
   * @todo
   */
  public function testStringToGroupAutoCreate() {
    $contact = $this->createContact();
    $group_storage = $this->container->get('entity_type.manager')->getStorage('contact_group');
    $user = $this->randomUser();
    $this->container->get('account_switcher')->switchTo($user);

    // Verify that the groups did not initially exist.
    $this->assertEmpty($group_storage->loadByProperties(['name' => 'group1']));
    $this->assertEmpty($group_storage->loadByProperties(['name' => 'group2']));
    $this->assertEmpty($group_storage->loadByProperties(['name' => 'group3']));
    $contact
      ->setOwner($user)
      ->setGroups(['group1', 'group2', 'group3'])
      ->save();

    // Verify that the groups are created from the strings supplied.
    $groups = $contact->getGroups();
    $this->assertEquals($group_storage->loadByProperties(['name' => 'group1'])[1], $groups[0]);
    $this->assertEquals($group_storage->loadByProperties(['name' => 'group2'])[2], $groups[1]);
    $this->assertEquals($group_storage->loadByProperties(['name' => 'group3'])[3], $groups[2]);

    // Also works for ::addGroups()
    $contact
      ->addGroups(['group4', 'group5', 'group6'])
      ->save();
    $groups = $contact->getGroups();
    $this->assertEquals('group4', $groups[3]->getName());
    $this->assertEquals('group5', $groups[4]->getName());
    $this->assertEquals('group6', $groups[5]->getName());
    $this->assertCount(6, $contact->getGroups());

    // Ensure duplicate groups are not created with the same name.
    $this->createContact()
      ->setOwner($user)
      ->setGroups(['group1', 'group2'])
      ->save();
    $this->assertCount(1, $group_storage->loadByProperties(['name' => 'group1']));
    $this->assertCount(1, $group_storage->loadByProperties(['name' => 'group2']));
    $this->assertCount(1, $group_storage->loadByProperties(['name' => 'group3']));

    // Also works for ::removeGroups()
    $contact
      ->removeGroups(['group1', 'group4', 'group6'])
      ->save();
    $groups = $contact->getGroups();
    $this->assertCount(3, $groups);
    $this->assertEquals('group2', $groups[0]->getName());
    $this->assertEquals('group3', $groups[1]->getName());
    $this->assertEquals('group5', $groups[2]->getName());
  }

  /**
   * Testing the \LogicException when setGroups(), addGroups() or removeGroups()
   *
   * @covers ::ensureValidContactGroups
   */
  public function testSetOwnerException() {
    $contact = $this->createContact();
    $user = $this->randomUser();
    $groups = $this->createContactGroups(['group1'], $user);
    $this->setExpectedException(\LogicException::class, 'Owner not set or saved, call setOwner() first before setting, adding or removing groups.');
    $contact
      ->setGroups($groups)
      ->setOwner($user);
  }

  /**
   * Testing the \InvalidArgumentException when setGroups is called.
   *
   * @covers ::ensureValidContactGroups
   */
  public function testInvalidContactGroup() {
    $user = $this->randomUser();
    $contact = $this->createContact();
    $this->setExpectedException(\InvalidArgumentException::class, 'Only strings or contact group entities are allowed, Drupal\user\Entity\User found');
    $contact
      ->setOwner($user)
      ->setGroups([$user]);
  }

}
