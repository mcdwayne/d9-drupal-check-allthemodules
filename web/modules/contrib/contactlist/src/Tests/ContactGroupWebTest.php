<?php

namespace Drupal\contactlist\Tests;

use Drupal\contactlist\Entity\ContactGroup;
use Drupal\contactlist\Entity\ContactGroupInterface;
use Drupal\contactlist\Entity\ContactListEntry;
use Drupal\contactlist\Entity\ContactListEntryInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests basic ContactListEntry functionality.
 *
 * @group ContactListEntry
 */
class ContactGroupWebTest extends WebTestBase {
  
  use ContactListTestTrait;

  protected $profile = 'testing';

  protected static $modules = ['block', 'contactlist'];

  /**
   * Tests CRUD of Contact lists using the UI.
   */
  public function testContactGroupUiCrud() {
    // Create test entities for the user1 and unrelated to a user.
    $user1 = $this->drupalCreateUser([
      'add contact list entry',
      'view contact list entry',
      'update contact list entry',
      'delete contact list entry'
    ]);
    $this->drupalLogin($user1);
    $this->drupalPlaceBlock('local_actions_block', ['region' => 'content', 'weight' => -2]);
    $this->drupalPlaceBlock('local_tasks_block', ['region' => 'content', 'weight' => -1]);

    // Confirm the listing page is empty for this user.
    $this->drupalGet('contactlist/group');
    $this->assertText('There is no Contact group yet.');
    $this->assertNoText('Edit');

    // Add new contact group using the UI - click the add menu link.
    $this->clickLink('Add contact group');
    $this->assertUrl('contactlist/group/add');
    $this->assertNoFieldByXPath('//input[@name="name"]');

    $edit = [
      'name[0][value]' => $this->randomMachineName(),
      'description[0][value]' => $this->randomMachineName(20),
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Confirm the listing page has the recently added contact.
    $this->drupalGet('contactlist/group');
    $this->assertNoText('There is no Contact group yet.');
    $this->assertText($edit['name[0][value]'], 'Contact group successfully added via UI.');
    /** @var \Drupal\contactlist\Entity\ContactGroup $group */
    $groups = ContactGroup::loadMultiple();
    $group = reset($groups);
    $this->assertEqual($edit['name[0][value]'], $group->getName());
    $this->assertEqual($edit['description[0][value]'], $group->getDescription());
    $this->assertEqual($user1->id(), $group->getOwner()->id(), 'Group assigned to the current user');

    // Confirm 'view' and 'delete' links exist, click 'edit' link.
    $this->assertLink('Delete', 0);
    $this->clickLink('Edit', 0);
    $this->assertUrl('contactlist/group/1?destination=' . $GLOBALS['base_path'] . 'contactlist/group');
    $this->assertFieldByXPath('//input[@name="name[0][value]"]', $edit['name[0][value]']);
    $this->assertFieldByXPath('//textarea[@name="description[0][value]"]', $edit['description[0][value]']);

    // Update the email and check that redirect goes to list page after saving in edit form.
    $edit1 = ['name[0][value]' => $this->randomMachineName(8)];
    $this->drupalPostForm(NULL, $edit1, t('Save'));
    $this->assertUrl('contactlist/group');
    $this->assertText($edit1['name[0][value]']);

    // Verify default owner was supplied.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $storage = $this->container->get('entity_type.manager')->getStorage('contact_group');
    $results = $storage->getQuery()
      ->condition('name', $edit1['name[0][value]'])
      ->execute();
    $storage->resetCache();
    $group = $storage->load(reset($results));
    $this->assertEqual($user1->id(), $group->getOwner()->id());
    $this->assertEqual($edit1['name[0][value]'], $group->getName());

    // Test the delete functionality.
    $this->clickLink('Delete');
    $this->assertUrl($group->toUrl('delete-form'));
    $this->assertTitle('Are you sure you want to delete the contact group ' . $group->getName() . '? | Drupal');
    $this->assertText('This action cannot be undone.');
    $this->drupalPostForm(NULL, [], 'Delete');
    $this->assertUrl('contactlist/group');
    $this->assertText('There is no Contact group yet.');
    $this->assertEqual([], $storage->loadMultiple());
  }

  /**
   * Tests the contact groups form features.
   */
  public function testContactGroupForm() {
    // Create test entities for the user1 and unrelated to a user.
    $user1 = $this->drupalCreateUser([
      'add contact list entry',
      'view contact list entry',
      'update contact list entry',
      'delete contact list entry'
    ]);
    $this->drupalLogin($user1);
    $group = $this->createContactGroups([$this->randomMachineName()], $user1)[0];
    // Confirm that the group doesn't show anything.
    $this->drupalGet($group->toUrl());
    $this->assertText('No contacts in this group.');

    $contact1 = $this->createContact()
      ->setContactName($this->randomMachineName())
      ->setOwner($user1)
      ->setPhoneNumber('2345678901')
      ->setEmail($this->randomMachineName() . '@example.com')
      ->setGroups([$group]);
    $contact1->save();

    // Confirm that the group now has contact as a member.
    $this->drupalGet($group->toUrl());
    $this->assertText('2345678901');
    $this->assertText($contact1->getContactName());

    $contact2 = $this->createContact()
      ->setContactName($this->randomMachineName())
      ->setOwner($user1)
      ->setPhoneNumber('1234567890')
      ->setEmail($this->randomMachineName() . '@example.com')
      ->setGroups([$group]);
    $contact2->save();

    // Confirm that the group now has two contacts as members.
    $this->drupalGet($group->toUrl());
    $this->assertText('2345678901');
    $this->assertText('1234567890');
    $this->assertText($contact1->getContactName());
    $this->assertText($contact2->getContactName());
    $this->assertInGroup($contact1, $group);
    $this->assertInGroup($contact2, $group);

    // Remove the contacts from the group and confirm removal.
    $edit = [
      "contacts[{$contact1->id()}][remove]" => FALSE,
      "contacts[{$contact2->id()}][remove]" => TRUE,
    ];
    $this->drupalPostForm($group->toUrl(), $edit, 'Save');
    $this->resetAll();
    $contact1 = ContactListEntry::load($contact1->id());
    $this->assertInGroup($contact1, $group);
    $contact2 = ContactListEntry::load($contact2->id());
    $this->assertNotInGroup($contact2, $group);

    // Remove the contacts from the group and confirm removal.
    $edit = ["contacts[{$contact1->id()}][remove]" => TRUE];
    $this->drupalPostForm($group->toUrl(), $edit, 'Save');
    $this->resetAll();
    $contact1 = ContactListEntry::load($contact1->id());
    $this->assertNotInGroup($contact1, $group);
  }

  /**
   * Test that a user cannot see other user's contacts.
   */
  public function testContactGroupAccessControl() {
    $user1 = $this->drupalCreateUser([
      'add contact list entry',
      'view contact list entry',
      'update contact list entry',
      'delete contact list entry'
    ]);
    $user2 = $this->drupalCreateUser([
      'add contact list entry',
      'view contact list entry',
      'update contact list entry',
      'delete contact list entry'
    ]);

    $this->createContactGroups(['group1', 'group2'], $user1);
    $this->createContactGroups(['group3'], $user2);

    $this->drupalLogin($user1);
    $this->drupalGet('contactlist/group');
    $this->assertText('group1');
    $this->assertText('group2');
    $this->assertNoText('group3');

    $this->drupalLogin($user2);
    $this->drupalGet('contactlist/group');
    $this->assertNoText('group1');
    $this->assertNoText('group2');
    $this->assertText('group3');
  }

  /**
   * Asserts that a contact list entry belongs to a group.
   *
   * @param \Drupal\contactlist\Entity\ContactListEntryInterface $contact
   * @param \Drupal\contactlist\Entity\ContactGroupInterface $contact_group
   * @param string $message
   * @param string $message_group
   * @param array|NULL $caller
   *
   * @return bool
   */
  protected function assertInGroup(ContactListEntryInterface $contact, ContactGroupInterface $contact_group, $message = '', $message_group = 'Other', array $caller = NULL) {
    return $this->assert(in_array($contact_group->label(), array_map(function (ContactGroupInterface $item) {
      return $item->label();
    }, $contact->getGroups())), $message ?: "{$contact->label()} is in group {$contact_group->label()}", $message_group, $caller);
  }

  /**
   * Asserts that a contact list entry does not belong to a group.
   *
   * @param \Drupal\contactlist\Entity\ContactListEntryInterface $contact
   * @param \Drupal\contactlist\Entity\ContactGroupInterface $contact_group
   * @param string $message
   * @param string $message_group
   * @param array|NULL $caller
   *
   * @return bool
   */
  protected function assertNotInGroup(ContactListEntryInterface $contact, ContactGroupInterface $contact_group, $message = '', $message_group = 'Other', array $caller = NULL) {
    return $this->assert(!in_array($contact_group->label(), array_map(function (ContactGroupInterface $item) {
      return $item->label();
    }, $contact->getGroups())), $message ?: "{$contact->label()} is not in group {$contact_group->label()}", $message_group, $caller);
  }

}
