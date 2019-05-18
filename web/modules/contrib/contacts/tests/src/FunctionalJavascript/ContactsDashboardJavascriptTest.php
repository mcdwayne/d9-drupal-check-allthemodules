<?php

namespace Drupal\Tests\contacts\Functional;

use Drupal\Core\Url;
use Drupal\decoupled_auth\Entity\DecoupledAuthUser;
use Drupal\file\Entity\File;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\image\Entity\ImageStyle;
use Drupal\profile\Entity\Profile;

/**
 * Tests CRM fields and views.
 *
 * @group contacts
 */
class ContactsDashboardJavascriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['contacts'];

  /**
   * Testing admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->createUser([], NULL, TRUE);
    // @TODO update page permission requirements.
    $this->adminUser->addRole('administrator');
    $this->adminUser->save();
  }

  /**
   * Create a contact of the given type.
   *
   * @param string $type
   *   The type of contact, either 'crm_indiv' or 'crm_org'.
   * @param bool $decoupled
   *   Whether the user should be decoupled. Defaults to TRUE.
   *
   * @return \Drupal\decoupled_auth\Entity\DecoupledAuthUser
   *   The contact that was created.
   */
  protected function createContact($type, $decoupled = TRUE) {
    // Create our user.
    $name = $this->randomMachineName();
    $contact = DecoupledAuthUser::create([
      'name' => $decoupled ? NULL : $name,
      'mail' => $name . '@example.com',
      'status' => 1,
    ]);
    $contact->addRole($type);
    $contact->save();

    // Generate a random image.
    $filesystem = $this->container->get('file_system');
    $tmp_file = $filesystem->tempnam('temporary://', 'contactImage_');
    $destination = $tmp_file . '.png';
    file_unmanaged_move($tmp_file, $destination, FILE_CREATE_DIRECTORY);
    $path = $this->getRandomGenerator()->image($filesystem->realpath($destination), '100x100', '100x100');
    $image = File::create();
    $image->setFileUri($path);
    $image->setOwnerId($contact->id());
    $image->setMimeType($this->container->get('file.mime_type.guesser')->guess($path));
    $image->setFileName($filesystem->basename($path));
    $destination = 'public://contactImage_' . $contact->id() . '.png';
    $file = file_move($image, $destination, FILE_CREATE_DIRECTORY);

    // Build our profile.
    switch ($type) {
      case 'crm_indiv':
        $values = [
          'type' => 'crm_indiv',
          'crm_name' => $this->randomString(20),
          'crm_gender' => 'female',
          'crm_address' => [
            'country_code' => 'GB',
            'locality' => $this->randomString(),
          ],
          'crm_photo' => $file->id(),
        ];
        break;

      case 'crm_org':
        $values = [
          'type' => 'crm_org',
          'crm_org_name' => $this->randomString(20),
          'crm_org_address' => [
            'country_code' => 'GB',
            'locality' => $this->randomString(),
          ],
          'crm_logo' => $file->id(),
        ];
        break;

      default:
        return $contact;
    }
    $values += [
      'uid' => $contact->id(),
      'status' => 1,
      'is_default' => 1,
    ];
    $profile = Profile::create($values);
    $profile->save();

    // @todo: Remove when onUpdate is added.
    $contact->updateProfileFields([$type]);
    return $contact;
  }

  /**
   * Sets up debugging output.
   */
  private function enableHtmlOutput() {
    $this->htmlOutputEnabled = TRUE;
    $this->htmlOutputDirectory = DRUPAL_ROOT . '/sites/simpletest';
    $this->htmlOutputFile = DRUPAL_ROOT . '/sites/simpletest/index.txt';
    $this->htmlOutputCounterStorage = DRUPAL_ROOT . '/sites/simpletest/counter';
  }

  /**
   * Test installing contacts and accessing the contact dashboard.
   */
  public function testViewDashboard() {
    // $this->enableHtmlOutput();
    // Create some same users.
    $contacts[] = DecoupledAuthUser::load(1);
    $contacts[] = $this->adminUser;
    $contacts[] = $this->createContact('crm_indiv');
    $contacts[] = $this->createContact('crm_indiv', FALSE);
    $contacts[] = $this->createContact('crm_org');

    // Gain access to the contacts dashboard.
    $this->drupalLogin($this->adminUser);

    // Make sure our items are indexed.
    $this->reIndex();

    // All scenarios are in 1 test to prevent multiple drupal installs.
    $this->checkAdminCanAccessDashboard();
    $this->checkContactsList($contacts);
    $new_user_id = $this->checkCanAddContact();
    $this->checkCanEditContact($new_user_id);
  }

  /**
   * Checks a contact can be added.
   *
   * @return string
   *   ID of the added user.
   */
  protected function checkCanAddContact() {
    // Click the "Plus" icon on the top of the main dashboard screen.
    $this->drupalGet('/admin/contacts');
    $page = $this->getSession()->getPage();
    $elem = $page->find('css', '#dropdown-menu-button');
    $elem->click();

    // Wait until the menu opens.
    $this->assertSession()->waitForElementVisible('css', '#local-action-group-contacts-contact-create-group');

    $elem = $page->find('css', "a[href='/admin/contacts/add/indiv']");
    $elem->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $first_name = $page->find('css', 'input[id^="edit-crm-name-0-given"]');
    $last_name = $page->find('css', 'input[id^="edit-crm-name-0-family"]');
    $email = $page->find('css', 'input[id^="edit-mail-0-value"]');

    $first_name->setValue('Jeremy1');
    $last_name->setValue('Skinner1');
    $email->setValue('jeremy1@jeremyskinner.co.uk');

    $button = $page->find('css', ".form-actions input[type='submit']");
    $button->click();

    // Extract user id from url of newly created user.
    $url = $this->getSession()->getCurrentUrl();
    preg_match('#admin/contacts/([0-9]+)#', $url, $matches);

    if (empty($matches[1])) {
      $this->fail('Failed to get user ID from the url.');
    }

    /* @var \Drupal\user\Entity\User $user */
    $user = \Drupal::entityTypeManager()->getStorage('user')
      ->loadUnchanged($matches[1]);

    // Check they have the email & name that were entered above.
    $this->assertEquals('jeremy1@jeremyskinner.co.uk', $user->getEmail());
    $this->assertEquals('Jeremy1', $user->profile_crm_indiv->entity->crm_name->given);
    $this->assertEquals('Skinner1', $user->profile_crm_indiv->entity->crm_name->family);

    return $matches[1];
  }

  /**
   * Checks that a contact can be edited through the dashboard.
   *
   * @param string $user_id
   *   ID of user to edit.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function checkCanEditContact($user_id) {
    $this->drupalGet('/admin/contacts/' . $user_id);

    // Click the Individual profile tab.
    $page = $this->getSession()->getPage();

    $tab = $page->find('css', 'a[data-contacts-tab-id="crm_indiv"]');
    $tab->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Find the edit button and click it.
    $edit = $page->find('css', "a[href='/admin/contacts/{$user_id}/indiv?edit=edit']");
    $edit->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Update the first name (leave last name as is)
    $page = $this->getSession()->getPage();
    $first_name = $page->find('css', 'input[name="crm_name[0][given]"]');
    $first_name->setValue('Jeremy2');

    // Submit the form.
    $page->pressButton('Save');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Validate the updated name appears in the page.
    $text = $page->find('css', 'div.field--name-crm-name .field-item')->getText();
    $this->assertEquals('Jeremy2 Skinner1', $text);
  }

  /**
   * Checks the dashboard loads.
   */
  protected function checkAdminCanAccessDashboard() {
    $session = $this->assertSession();
    // Check that the contacts dashboard has the expected content.
    $this->drupalGet('admin/contacts');
    $session->elementTextContains('css', 'h1', 'Contacts');
  }

  /**
   * Tests that the contacts list is shown.
   *
   * @param array $contacts
   *   Array of expected contacts.
   */
  protected function checkContactsList(array $contacts) {
    $session = $this->assertSession();
    // Sort our contacts.
    usort($contacts, [ContactsDashboardTest::class, 'sortContacts']);

    // Load our image style for building URLs.
    $style = ImageStyle::load('contacts_small');

    // Check our expected users are listed.
    $index = 1;
    foreach ($contacts as $contact) {
      // Gather our relevant values.
      $values = [];

      $roles = user_roles();
      uasort($roles, 'contacts_sort_roles');
      $roles = array_map(function ($item) {
        return $item->label();
      }, $roles);
      $values['roles'] = implode(', ', array_intersect_key($roles, array_fill_keys($contact->getRoles(), TRUE)));
      $values['email'] = $contact->getEmail();
      $values['image'] = $contact->user_picture[0] ? $contact->user_picture[0]->entity->getFileUri() : FALSE;
      $values['label'] = $contact->getDisplayName();

      if ($contact->hasRole('crm_indiv')) {
        $profile = $contact->profile_crm_indiv->entity;
        $values['city'] = $profile->crm_address->locality;

        if ($profile->crm_photo->target_id) {
          $values['image'] = $profile->crm_photo->entity->getFileUri();
        }

        if (!$values['image']) {
          $values['image'] = 'contacts://images/default-indiv.png';
        }
      }
      elseif ($contact->hasRole('crm_org')) {
        $profile = $contact->profile_crm_org->entity;
        $values['city'] = $profile->crm_org_address->locality;
        if (!$values['image']) {
          $values['image'] = 'contacts://images/default-org.png';
        }
      }
      else {
        $values['city'] = FALSE;
        if (!$values['image']) {
          $values['image'] = 'contacts://images/default-indiv.png';
        }
      }

      // Convert the image URI to a URL.
      $values['image'] = file_url_transform_relative(file_create_url($style->buildUri($values['image'])));
      $values['url'] = Url::fromRoute('page_manager.page_view_contacts_dashboard_contact', [
        'user' => $contact->id(),
      ])->toString();

      // Check our row is correctly rendered.
      $base_selector = "div.views-row:nth-of-type({$index}) ";

      // Check our row link.
      $session->elementAttributeContains('css', $base_selector, 'data-row-link', $values['url']);

      // Check the image.
      $session->elementAttributeContains('css', $base_selector . '.contacts-row-image a', 'href', $values['url']);
      $session->elementAttributeContains('css', $base_selector . '.contacts-row-image img', 'src', $values['image']);

      // Check the label.
      if ($values['label']) {
        $session->elementAttributeContains('css', $base_selector . '.contacts-row-main h4.contact-label a', 'href', $values['url']);
        $session->elementTextContains('css', $base_selector . '.contacts-row-main h4.contact-label a', $values['label']);
      }
      else {
        $session->elementNotExists('css', $base_selector . '.contacts-row-main h4.contact-label');
      }

      // Check the email.
      if ($values['email']) {
        $session->elementTextContains('css', $base_selector . '.contacts-row-main .contact-email', $values['email']);
      }
      else {
        $session->elementNotExists('css', $base_selector . '.contacts-row-main .contact-email');
      }

      // Check the city.
      if ($values['city']) {
        $session->elementTextContains('css', $base_selector . '.contacts-row-main .contact-address', $values['city']);
      }
      else {
        $session->elementNotExists('css', $base_selector . '.contacts-row-main .contact-address');
      }

      // Check the roles.
      if ($values['roles']) {
        $session->elementTextContains('css', $base_selector . '.contacts-row-main .contact-roles', $values['roles']);
      }
      else {
        $session->elementNotExists('css', $base_selector . '.contacts-row-main .contact-roles');
      }

      // Check the ID.
      $session->elementTextContains('css', $base_selector . '.contacts-row-supporting small.contact-id', 'ID: ' . $contact->id());

      $index++;
    }
  }

  /**
   * Re-indexes contacts.
   */
  protected function reIndex(): void {
    /* @var \Drupal\search_api\IndexInterface $index */
    $index = \Drupal::entityTypeManager()
      ->getStorage('search_api_index')
      ->load('contacts_index');
    $index->indexItems();
  }

}
