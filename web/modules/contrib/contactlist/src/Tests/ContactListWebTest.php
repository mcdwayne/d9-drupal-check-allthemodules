<?php

namespace Drupal\contactlist\Tests;

use Drupal\contactlist\ContactGroupHelper;
use Drupal\contactlist\Entity\ContactGroup;
use Drupal\contactlist\Entity\ContactListEntry;
use Drupal\simpletest\WebTestBase;

/**
 * Tests basic ContactListEntry functionality.
 *
 * @group ContactListEntry
 */
class ContactListWebTest extends WebTestBase {

  use ContactListTestTrait;

  protected $profile = 'testing';

  protected static $modules = ['block', 'contactlist'];

  /**
   * Tests CRUD of Contact lists using the UI.
   */
  public function testContactlistUiCrud() {
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
    $this->drupalGet('contactlist');
    $this->assertText('There is no Contact list entry yet.');
    $this->assertNoText('Edit');

    // Add new contact using the UI - click the add menu link.
    $this->clickLink('Add contact');
    $this->assertUrl('contactlist/add');

    $edit = [
      'name[0][value]' => $this->randomMachineName(8),
      'telephone[0][value]' => '99' . rand(00000000, 99999999),
      'email[0][value]' => $this->randomMachineName(8) . '@example.com',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Confirm the listing page has the recently added contact.
    $this->drupalGet('contactlist');
    $this->assertNoText('There is no Contact list entry yet.');
    $this->assertText($edit['name[0][value]'], 'Contact successfully added via UI.');
    /** @var \Drupal\contactlist\Entity\ContactListEntry $contact */
    $contacts = ContactListEntry::loadMultiple();
    $contact = reset($contacts);
    $this->assertEqual($edit['name[0][value]'], $contact->getContactName());
    $this->assertEqual($edit['email[0][value]'], $contact->getEmail());
    $this->assertEqual($edit['telephone[0][value]'], $contact->getPhoneNumber());
    $this->assertEqual($user1->id(), $contact->getOwner()->id(), 'Contact assigned to the current user');

    // Confirm 'view' and 'delete' links exist, click 'edit' link.
    $this->assertLink('View', 0);
    $this->assertLink('Delete', 0);
    $this->clickLink('Edit', 0);
    $this->assertUrl('contactlist/1/edit?destination=' . $GLOBALS['base_path'] . 'contactlist');
    $this->assertFieldByXPath('//input[@name="name[0][value]"]', $edit['name[0][value]']);
    $this->assertFieldByXPath('//input[@name="telephone[0][value]"]', $edit['telephone[0][value]']);
    $this->assertFieldByXPath('//input[@name="email[0][value]"]', $edit['email[0][value]']);

    // Update the email and check that redirect goes to list page after saving in edit form.
    $edit1 = ['name[0][value]' => $this->randomMachineName(8)];
    $this->drupalPostForm(NULL, $edit1, t('Save'));
    $this->assertUrl('contactlist');
    $this->assertText($edit1['name[0][value]']);

    // Verify default owner was supplied.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $storage = $this->container->get('entity_type.manager')->getStorage('contactlist_entry');
    $results = $storage->getQuery()
      ->condition('name', $edit1['name[0][value]'])
      ->execute();
    $storage->resetCache();
    $contact = $storage->load(reset($results));
    $this->assertEqual($contact->getOwner()->id(), $user1->id());
    $this->assertEqual($contact->getContactName(), $edit1['name[0][value]']);

    // Test the delete functionality.
    $this->clickLink('Delete');
    $this->assertUrl($contact->toUrl('delete-form'));
    $this->assertTitle('Are you sure you want to delete the contact list entry ' . $contact->label() . '? | Drupal');
    $this->assertText('This action cannot be undone.');
    $this->drupalPostForm(NULL, [], 'Delete');
    $this->assertUrl('contactlist');
    $this->assertText('The contact list entry ' . $contact->getContactName() . ' has been deleted.');
    $this->assertText('There is no Contact list entry yet.');
    $this->assertEqual([], $storage->loadMultiple());
  }

  /**
   * Tests the contact group widget in the contact entry edit form.
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
    $edit = [
      'name[0][value]' => $this->randomMachineName(8),
      'telephone[0][value]' => '99' . rand(00000000, 99999999),
      'email[0][value]' => $this->randomMachineName(8) . '@example.com',
      'groups[target_id]' => 'group1, group2, group3',
    ];

    // Create one of the groups and allow the other 2 to be auto-created.
    $this->assertEqual(0, count(ContactGroup::loadMultiple()));
    ContactGroup::create()->setName('group1')->save();
    $this->assertEqual(1, count(ContactGroup::loadMultiple()));

    // Create the contact and verify the other 2 groups are auto-created.
    $this->drupalPostForm('contactlist/add', $edit, 'Save');
    $this->assertEqual(3, count(ContactGroup::loadMultiple()));

    // Assert the contact is created with the right groups.
    $contacts = ContactListEntry::loadMultiple();
    $contact = reset($contacts);
    $groups = $contact->getGroups();
    $this->assertEqual(3, count($groups));
    $this->assertEqual('group1', $groups[0]->getName());
    $this->assertEqual('group2', $groups[1]->getName());
    $this->assertEqual('group3', $groups[2]->getName());

    // Verify that the groups are properly displayed in the contact listing.
    $this->drupalGet('contactlist');
    $this->assertEqual($edit['groups[target_id]'], ContactGroupHelper::viewAsTags($contact->getGroups()));

    // Verify that the default values are still displayed in edit form.
    $this->drupalGet('contactlist/' . $contact->id() . '/edit');
    $this->assertFieldByName('groups[target_id]', 'group1 (1), group2 (2), group3 (3)');
    $edit['groups[target_id]'] = 'group1';
    $this->drupalPostForm(NULL, $edit, 'Save');
    // Reload and verify changes.
    $this->container->get('entity_type.manager')->getStorage('contactlist_entry')->resetCache();
    $contact = ContactListEntry::load($contact->id());
    $this->assertEqual(1, count($contact->getGroups()));
  }

  /**
   * Tests that the autocomplete widget doesn't show other users' contacts.
   */
  public function testAutocompleteWidgetAccessControl() {
    // Create test entities for the user1 and unrelated to a user.
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
    // Create  contact and groups for one user.
    $this->drupalLogin($user1);
    $edit = [
      'name[0][value]' => $this->randomMachineName(8),
      'telephone[0][value]' => '99' . rand(00000000, 99999999),
      'email[0][value]' => $this->randomMachineName(8) . '@example.com',
      'groups[target_id]' => 'group1, group2, group3',
    ];
    $this->drupalPostForm('contactlist/add', $edit, 'Save');

    // Create contact and groups for another user.
    $this->drupalLogin($user2);
    $edit['groups[target_id]'] = 'group1, group2, group3';
    $this->drupalPostForm('contactlist/add', $edit, 'Save');
    
    // There should be six contact groups altogether and 2 contact entries
    $this->assertEqual(6, count(ContactGroup::loadMultiple()));
    $this->assertEqual(2, count(ContactListEntry::loadMultiple()));
  }

  /**
   * Tests that a user cannot see other users' contacts.
   */
  public function testContactListAccessControl() {
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

    $this->createContact()
      ->setPhoneNumber('123455667')
      ->setContactName('contact1')
      ->setOwner($user1)
      ->save();

    $this->createContact()
      ->setPhoneNumber('2345566789')
      ->setContactName('contact2')
      ->setOwner($user1)
      ->save();

    $this->createContact()
      ->setPhoneNumber('3455667890')
      ->setContactName('contact3')
      ->setOwner($user2)
      ->save();

    $this->createContact()
      ->setPhoneNumber('4556678901')
      ->setContactName('contact4')
      ->setOwner($user1)
      ->save();

    $this->drupalLogin($user1);
    $this->drupalGet('contactlist');
    $this->assertText('123455667');
    $this->assertText('2345566789');
    $this->assertNoText('3455667890');
    $this->assertText('4556678901');

    $this->drupalLogin($user2);
    $this->drupalGet('contactlist');
    $this->assertNoText('123455667');
    $this->assertNoText('2345566789');
    $this->assertText('3455667890');
    $this->assertNoText('4556678901');
  }

}
