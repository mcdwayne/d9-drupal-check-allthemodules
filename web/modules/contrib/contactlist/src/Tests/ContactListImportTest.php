<?php

namespace Drupal\contactlist\Tests;

use Drupal\contactlist\Entity\ContactGroup;
use Drupal\contactlist\Entity\ContactListEntry;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the contact list import functionality.
 *
 * @group ContactListEntry
 */
class ContactListImportTest extends WebTestBase {

  public static $modules = ['contactlist', 'file'];

  /**
   * Tests quick imports using the import form.
   */
  public function testQuickImportForm() {
    // Create a test user.
    $user = $this->drupalCreateUser(['add contact list entry', 'view contact list entry', 'update contact list entry', 'delete contact list entry']);
    $this->drupalLogin($user);

    // Confirm that the default upload form is available.
    $this->drupalGet(new Url('contactlist.quick_import'));
    $this->assertField('free_text');

    $numbers = [];
    for ($i = 0; $i < 5; $i++) {
      $numbers[] = rand(10000000, 99999999);
    }
    $edit = ['free_text' => implode("\n", $numbers)];
    $this->drupalPostForm(NULL, $edit, 'Import');
    $this->assertResponse(200);
    $this->assertText('5 contact list entries successfully imported.');
    $this->assertNoText('Free text field is required.');
    $this->assertUrl(new Url('entity.contactlist_entry.collection'));

    foreach ($numbers as $number) {
      $this->assertText($number);
    }

    // Test empty submission.
    $this->drupalPostForm(new Url('contactlist.quick_import'), [], 'Import');
    $this->assertText('Free text field is required.');
    $this->assertUrl(new Url('contactlist.quick_import'));
    
    // Test with default groups entry and verify contacts are added to groups.
    $edit = [
      'free_text' => "1234567890\n2345678901\n3456789012\n4567890123",
      'groups[target_id]' => 'group1, group2',
    ];
    $this->drupalPostForm(NULL, $edit, 'Import');
    $this->assertResponse(200);
    $this->assertText('4 contact list entries successfully imported.');

    $this->drupalGet('contactlist');
    $this->assertText('1234567890');
    $this->assertText('group1, group2');

    $contacts = ContactListEntry::loadMultiple();
    $this->assertEqual(9, count($contacts));
    $contact = array_pop($contacts);
    $this->assertEqual(2, count($contact->getGroups()));
    $this->assertEqual('group1', $contact->getGroups()[0]->getName());
    $this->assertEqual('group2', $contact->getGroups()[1]->getName());

  }

  /**
   * Tests the advanced import form with all the settings.
   */
  public function testAdvancedImportForm() {
    // Create a test user.
    $user = $this->drupalCreateUser([
      'add contact list entry',
      'view contact list entry',
      'update contact list entry',
      'delete contact list entry'
    ]);
    $this->drupalLogin($user);

    // Confirm that the default upload form is available.
    $this->drupalGet(new Url('contactlist.advanced_import'));
    $this->assertField('csv_text');
    $edit = ['csv_text' => $this->getCsvText()];
    $this->drupalPostAjaxForm(NULL, $edit, ['op' => 'Preview']);
    $this->assertResponse(200);
    $this->assertText('2348030783839');
    $this->assertText('noreply@example.com');
    $this->assertText('Jolly');
    $this->assertText('Nolly');
    $this->assertText('Polly');
    $this->assertText('Solly');
    $this->assertText('Lolly');
    $this->assertText('Wolly');

    $this->drupalPostForm(NULL, $edit, 'Import');
    $this->assertText('6 contact list entries successfully imported.');
    $this->assertNoText('Contact list entries field is required.');
    $this->assertUrl(new Url('entity.contactlist_entry.collection'));

    // Confirm that contact groups were correctly imported.
    $group_storage = $this->container->get('entity_type.manager')->getStorage('contact_group');
    $group1 = $group_storage->loadByProperties(['name' => 'Group 1']);
    $group1 = reset($group1);
    $group2 = $group_storage->loadByProperties(['name' => 'Group 2']);
    $group2 = reset($group2);
    $this->assertEqual(3, count($group1->getContacts()));
    $this->assertEqual(1, count($group2->getContacts()));

    $contact_storage = $this->container->get('entity_type.manager')->getStorage('contactlist_entry');
    $contact = $contact_storage->loadByProperties(['name' => 'Polly']);
    $contact = reset($contact);
    $this->assertEqual(0, count($contact->getGroups()));

    // Test empty submission.
    $this->drupalPostForm(new Url('contactlist.advanced_import'), [], 'Import');
    $this->assertText('Contact list entries field is required.');
    $this->assertUrl(new Url('contactlist.advanced_import'));
  }

  /**
   * Tests the advanced import form with all the settings.
   */
  public function testAdvancedImportWithTabSeparated() {
    // Create a test user.
    $user = $this->drupalCreateUser([
      'add contact list entry',
      'view contact list entry',
      'update contact list entry',
      'delete contact list entry'
    ]);
    $this->drupalLogin($user);

    // Confirm that the default upload form is available.
    $this->drupalPostForm(new Url('contactlist.advanced_import'), ['csv_text' => str_replace(',', "\t", $this->getCsvText())], 'Import');
    $this->assertResponse(200);
    $this->assertText('6 contact list entries successfully imported.');
    $this->assertUrl(new Url('entity.contactlist_entry.collection'));

  }

  public function testAdvancedImportWithInvalidData() {
    // Create a test user.
    $user = $this->drupalCreateUser(['add contact list entry', 'view contact list entry', 'update contact list entry', 'delete contact list entry']);
    $this->drupalLogin($user);

    // Confirm that the default upload form is available.
    $this->drupalPostForm(new Url('contactlist.advanced_import'), ['csv_text' => $this->getCsvText()], 'Import');
    $this->assertResponse(200);
    $this->assertText('6 contact list entries successfully imported.');
    $this->assertUrl(new Url('entity.contactlist_entry.collection'));

    // Confirm that duplicate entries are skipped.
    // @todo tests for duplicate entries.

    // @todo tests for invalid entries.

  }

  /**
   * Tests the import of bulk contacts from CSV files.
   */
  public function testBulkImportForm() {
    // Create a test user.
    $user = $this->drupalCreateUser([
      'add contact list entry',
      'view contact list entry',
      'update contact list entry',
      'delete contact list entry'
    ]);
    $this->drupalLogin($user);

    // Confirm that the default upload form is available and test file preview
    // and upload.
    $this->drupalGet(new Url('contactlist.bulk_import'));
    $this->assertField('files[csv_file]');
    $file = realpath(__DIR__ . '/../../tests/files/sample_contact_list.csv');
    $edit = ['files[csv_file]' => $file];
    $this->drupalPostAjaxForm(NULL, $edit, ['op' => 'Preview']);
    $this->assertResponse(200);
    $this->assertText('2348030783839');
    $this->assertText('noreply@example.com');
    $this->assertText('Jolly');
    $this->assertText('Nolly');
    $this->assertText('Polly');
    $this->assertText('Solly');
    $this->assertText('Lolly');
    $this->assertText('Wolly');

    $this->drupalPostForm(NULL, $edit, 'Import');
    $this->assertText('6 contact list entries successfully imported.');
    $this->assertNoText('CSV file not specified or found.');
    $this->assertUrl(new Url('entity.contactlist_entry.collection'));

    // Test empty submission.
    $this->drupalPostForm(new Url('contactlist.bulk_import'), [], 'Import');
    $this->assertText('CSV file not specified or found.');
    $this->assertUrl(new Url('contactlist.bulk_import'));

    // @todo Add tests for not importing the entire thing in a large CSV file.
  }

  /**
   * Tests quick import with data that has lots of blank spaces.
   */
  public function testQuickImportWithFlawedData() {
    // Create a test user.
    $user = $this->drupalCreateUser(['add contact list entry', 'view contact list entry', 'update contact list entry', 'delete contact list entry']);
    $this->drupalLogin($user);

    // Test with empty CRLF-separated lines mixed with LF-separated lines.
    $edit = [
      'free_text' => "\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n1234567890\n\r\n\n\n2345678901\n\r\n\r\n\r\n3456789012\n\n\n\n\n4567890123\n\n\n\n",
      'groups[target_id]' => '',
    ];
    $url = new Url('contactlist.quick_import');
    $this->drupalPostForm($url, $edit, 'Import');
    $this->assertResponse(200);
    $this->assertText('4 contact list entries successfully imported.');
    $this->assertUrl(new Url('entity.contactlist_entry.collection'));

    // Try a comma-delimited set of numbers.
    $edit = [
      'free_text' => "2347076865757,\n2347023797088,\n2347076865757,\n2347023797088,\n2347076865757,\n2347023797088",
    ];
    $this->drupalPostForm($url, $edit, 'Import');
    $this->assertResponse(200);
    $this->assertText('6 contact list entries successfully imported.');
    $this->assertUrl(new Url('entity.contactlist_entry.collection'));
  }

  /**
   * Tests with new and existing groups.
   */
  public function testQuickImportWithGroups() {
    // Create a test user.
    $user = $this->drupalCreateUser(['add contact list entry', 'view contact list entry', 'update contact list entry', 'delete contact list entry']);
    $this->drupalLogin($user);

    // Test import contacts with a new group.
    $edit = [
      'free_text' => "1234567890\n2345678901\n",
      'groups[target_id]' => 'New group',
    ];
    $url = new Url('contactlist.quick_import');
    $this->drupalPostForm($url, $edit, 'Import');
    $this->assertResponse(200);
    $this->assertText('2 contact list entries successfully imported.');
    $this->assertUrl(new Url('entity.contactlist_entry.collection'));

    // Verify groups.
    $groups = ContactGroup::loadMultiple();
    $this->assertEqual(1, count($groups));
    $second_group = reset($groups);
    $this->assertEqual('New group', $second_group->getName());

    // Test import contacts with existing group.
    ContactGroup::create()
      ->setOwner($user)
      ->setDescription('The second group')
      ->setName('Second group')
      ->save();
    $edit = [
      'free_text' => "2347076865757\n2347023797088\n",
      'groups[target_id]' => 'Second group (2)',
    ];
    $this->drupalPostForm($url, $edit, 'Import');
    $this->assertResponse(200);
    $this->assertText('2 contact list entries successfully imported.');
    $this->assertEqual(4, count(ContactListEntry::loadMultiple()));
    $this->assertUrl(new Url('entity.contactlist_entry.collection'));

    // Verify groups.
    $groups = ContactGroup::loadMultiple();
    $this->assertEqual(2, count($groups));
    $second_group = $groups[2];
    $this->assertEqual('Second group', $second_group->getName());
  }

  /**
   * Tests bulk import with data that has lots of blank spaces.
   */
  public function testBulkImportWithFlawedData() {
    // Create a test user.
    $user = $this->drupalCreateUser([
      'add contact list entry',
      'view contact list entry',
      'update contact list entry',
      'delete contact list entry'
    ]);
    $this->drupalLogin($user);

    // Test with empty CRLF-separated lines mixed with LF-separated lines.
    $free_text = "\r\n\r\n\r\nname,email,phone number\r\n\r\n\r\n\r\n\r\n\r\nkevwe,kevwe@example.org,1234567890\n\r\n\n\nkevwe1,kevwe1@example.org,2345678901\n\r\n\r\n\r\nkevwe2,kevwe2@example.org,3456789012\n\n\n\n\nglad,glad@example.org,4567890123\n\n\n\n";
    $csv_file = 'public://sample_contact_list_flawed.csv';
    $fp = fopen($csv_file, 'w');
    fwrite($fp, $free_text);
    fclose($fp);

    // Confirm that the default upload form is available and test file preview
    // and upload.
    $url = Url::fromRoute('contactlist.bulk_import');
    $edit = ['files[csv_file]' => $csv_file];
    $this->drupalGet($url);
    $this->assertText('Bulk Contact list import');
    $this->drupalPostAjaxForm(NULL, $edit, ['op' => 'Preview']);
    $this->assertResponse(200);
    $this->assertText('1234567890');
    $this->assertText('2345678901');
    $this->assertText('3456789012');
    $this->assertText('4567890123');
    $this->assertText('kevwe@example.org');
    $this->assertText('kevwe1@example.org');
    $this->assertText('kevwe2@example.org');
    $this->assertText('glad@example.org');
    $this->assertText('kevwe');
    $this->assertText('kevwe1');
    $this->assertText('kevwe2');
    $this->assertText('glad');

    $this->drupalPostForm($url, $edit, 'Import');
    $this->assertText('4 contact list entries successfully imported.');
    $contacts = ContactListEntry::loadMultiple();
    $this->assertEqual(4, count($contacts));
    $this->assertEqual('kevwe', $contacts[1]->getContactName());
    $this->assertEqual('kevwe1', $contacts[2]->getContactName());
    $this->assertEqual('kevwe2', $contacts[3]->getContactName());
    $this->assertEqual('glad', $contacts[4]->getContactName());
  }

  protected function getCsvText() {
    return <<<CSV
NAME,PHONE,MOBILE,EMAIL,CITY,COUNTRY,BIRTH_DAY,WORK,GROUPS,ACTIVE_ROLES,WANTED_ROLES
Jolly,2348030783839,,noreply@example.com,NoCity,NoCountry,38758,My work,Group 1,,
Nolly,2348038983839,2348030783839,noreply@example.com,NoCity,NoCountry,38758,My work,"Group 1, Group 2",,
Polly,2348030783839,2348030783839,noreply@example.com,NoCity,NoCountry,38758,My work,,,
Solly,2348030783457,2348030783839,3reply@example.com,NoCity,NoCountry,38758,My work,Group 3,,
Lolly,2347090783839,,noreply@example.com,NoCity,NoCountry,38758,My work,"Group 1, Group 3",,
Wolly,2347090783234,,1reply@example.com,NoCity,NoCountry,38758,My work,Group 6,,
CSV;
  }

}
