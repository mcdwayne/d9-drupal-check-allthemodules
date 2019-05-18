<?php

namespace Drupal\crm_core_contact\Tests;

use Drupal\crm_core_contact\Entity\Individual;
use Drupal\crm_core_contact\Entity\IndividualType;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the UI for Individual CRUD operations.
 *
 * @group crm_core
 */
class IndividualUiTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'crm_core_contact',
    'crm_core_activity',
    'crm_core_tests',
    'block',
    'views_ui',
    'options',
    'datetime',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    IndividualType::create([
      'name' => 'Customer',
      'type' => 'customer',
      'description' => 'A single customer.',
      'primary_fields' => [],
    ])->save();

    // Place local actions and local task blocks.
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests the individual operations.
   *
   * User with permissions 'administer crm_core_individual entities'
   * should be able to create/edit/delete individuals of any individual type.
   */
  public function testIndividualOperations() {
    $this->drupalGet('crm-core');
    $this->assertResponse(403);

    $user = $this->drupalCreateUser([
      'view any crm_core_individual entity',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('crm-core');
    $this->assertLink('CRM Individuals');
    $this->assertNoLink('CRM Activities');

    $user = $this->drupalCreateUser([
      'view any crm_core_activity entity',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('crm-core');
    $this->assertNoLink('CRM Individuals');
    $this->assertLink('CRM Activities');

    $this->assertNoLinkByHref('crm-core/individual/add/customer', 'User has no permission to create Customer individuals.');
    $this->drupalGet('crm-core/individual/add/customer');
    $this->assertResponse(403);

    // Create user and login.
    $user = $this->drupalCreateUser([
      'delete any crm_core_individual entity of bundle customer',
      'create crm_core_individual entities of bundle customer',
      'view any crm_core_individual entity',
      'view any crm_core_activity entity',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('crm-core');

    $this->assertTitle(t('CRM Core | Drupal'));

    $this->assertLink(t('CRM Activities'));
    $this->assertLink(t('CRM Individuals'));
    $this->clickLink(t('CRM Individuals'));
    // There should be no individuals available after fresh installation and
    // there is a link to create new individuals.
    $this->assertText(t('There are no individuals available.'), 'No individuals available after fresh installation.');
    $this->assertLink(t('Add an individual'));

    $this->drupalGet('crm-core/individual/add');
    $this->assertUrl('crm-core/individual/add/customer');

    // Create individual customer.
    $user = $this->drupalCreateUser([
      'delete any crm_core_individual entity of bundle customer',
      'create crm_core_individual entities',
      'edit any crm_core_individual entity',
      'administer individual types',
      'view any crm_core_individual entity',
    ]);
    $this->drupalLogin($user);
    $customer_node = [
      'name[0][title]' => 'Mr.',
      'name[0][given]' => 'John',
      'name[0][middle]' => 'Emanuel',
      'name[0][family]' => 'Smith',
      'name[0][generational]' => 'IV',
      'name[0][credentials]' => '',
    ];
    $this->drupalPostForm('crm-core/individual/add/customer', $customer_node, 'Save Customer');

    // Assert we were redirected back to the list of individuals.
    $this->assertUrl('crm-core/individual');

    $this->assertLink('John Smith', 0, 'Newly created individual title listed.');
    $this->assertText(t('Customer'), 'Newly created individual type listed.');

    // Assert all view headers are available.
    $this->assertLink('Name');
    $this->assertLink('Individual Type');
    $this->assertLink('Updated');
    $this->assertText('Operations links');

    $count = $this->xpath('//form[@class="views-exposed-form"]/div/div/label[text()="Name (given)"]');
    $this->assertTrue($count, 1, 'Name given is an exposed filter.');

    $count = $this->xpath('//form[@class="views-exposed-form"]/div/div/label[text()="Name (family)"]');
    $this->assertTrue($count, 1, 'Name given is an exposed filter.');

    $count = $this->xpath('//form[@class="views-exposed-form"]/div/div/label[text()="Type"]');
    $this->assertTrue($count, 1, 'Contact type is an exposed filter.');

    $individuals = \Drupal::entityTypeManager()->getStorage('crm_core_individual')->loadByProperties(['name__given' => 'John', 'name__family' => 'Smith']);
    $individual = current($individuals);

    $this->assertLinkByHref('crm-core/individual/' . $individual->id());

    $this->assertRaw('crm-core/individual/' . $individual->id() . '/edit', 'Edit link is available.');
    $this->assertRaw('crm-core/individual/' . $individual->id() . '/delete', 'Delete link is available.');

    $this->assertText($this->container->get('date.formatter')->format($individual->get('changed')->value, 'medium'), 'Individual updated date is available.');

    $this->drupalGet('crm-core/individual/1/edit');
    $this->assertRaw('crm-core/individual/1/delete" class="button button--danger" data-drupal-selector="edit-delete" id="edit-delete"', 'Delete link is available.');
    $this->assertRaw('nav class="tabs" role="navigation" aria-label="Tabs"');

    $individual->save();

    // Get test view data page.
    $this->drupalGet('individual-view-data');
    $this->assertText('Mr. John Emanuel Smith IV');

    // Edit customer individual.
    $customer_node = [
      'name[0][title]' => 'Mr.',
      'name[0][given]' => 'Maynard',
      'name[0][middle]' => 'James',
      'name[0][family]' => 'Keenan',
      'name[0][generational]' => 'I',
      'name[0][credentials]' => 'MJK',
    ];
    $individuals = $this->container->get('entity_type.manager')->getStorage('crm_core_individual')->loadByProperties(['name__given' => 'John', 'name__family' => 'Smith']);
    $individual = current($individuals);
    $this->drupalPostForm('crm-core/individual/' . $individual->id() . '/edit', $customer_node, 'Save Customer');
    // Assert we are viewing the updated entity after update.
    $this->assertUrl('crm-core/individual/' . $individual->id());

    $this->assertRaw('data-drupal-link-system-path="crm-core/individual/' . $individual->id() . '/delete"', 'Local task "Delete" is available.');

    // Check listing page.
    $this->drupalGet('crm-core/individual');
    $this->assertText('Maynard Keenan', 0, 'Updated customer individual title listed.');

    // Delete individual contact.
    $this->drupalPostForm('crm-core/individual/' . $individual->id() . '/delete', [], t('Delete'));
    $this->assertUrl('crm-core/individual');
    $this->assertNoLink('Maynard Keenan', 0, 'Deleted individual customer title no more listed.');

    // Assert that there are no contacts left.
    $this->assertText(t('There are no individuals available.'), 'No individuals available after deleting all of them.');

    // Create a individual with no label.
    /** @var \Drupal\crm_core_contact\ContactInterface $individual */
    $individual = Individual::create(['type' => 'customer']);
    $individual->save();

    // Create another user.
    $new_user = $this->drupalCreateUser();

    // Test EntityOwnerTrait functions on contact.
    $this->assertEqual($individual->getOwnerId(), $user->id());
    $this->assertEqual($individual->getOwner()->id(), $user->id());
    $individual->setOwner($new_user);
    $this->assertEqual($individual->getOwnerId(), $new_user->id());
    $this->assertEqual($individual->getOwner()->id(), $new_user->id());
    $individual->setOwnerId($user->id());
    $this->assertEqual($individual->getOwnerId(), $user->id());
    $this->assertEqual($individual->getOwner()->id(), $user->id());

    // Go to overview page and assert there is a default label displayed.
    $this->drupalGet('crm-core/individual');
    $this->assertLink('Nameless #' . $individual->id());
    $this->assertLinkByHref('crm-core/individual/' . $individual->id());
  }

  /**
   * Tests the individual type operations.
   *
   * User with permissions 'administer individual types' should be able to
   * create/edit/delete individual types.
   */
  public function testIndividualTypeOperations() {
    // Given I am logged in as a user with permission 'administer individual
    // types'.
    $user = $this->drupalCreateUser(['administer individual types']);
    $this->drupalLogin($user);

    // When I visit the individual type admin page.
    $this->drupalGet('admin/structure/crm-core/individual-types');

    // Then I should see edit, and delete links for existing contacts.
    $this->assertIndividualTypeLink('customer', 'Edit link for customer.');
    $this->assertIndividualTypeLink('customer/delete', 'Delete link for customer.');

    // Given there is a individual of type 'customer.'.
    Individual::create(['type' => 'customer'])->save();

    // When I visit the individual type admin page.
    $this->drupalGet('admin/structure/crm-core/individual-types');

    // Then I should not see a delete link.
    $this->assertNoIndividualTypeLink('customer/delete', 'No delete link for individual.');
    $this->drupalGet('admin/structure/crm-core/individual-types/customer/delete');
    $this->assertResponse(403);

    // When I edit the individual type.
    $this->drupalGet('admin/structure/crm-core/individual-types/customer');
    $this->assertResponse(200);

    // Then I should see "Save customer type" button.
    $this->assertRaw(t('Save individual type'), 'Save individual type button is present.');
    // Then I should not see a delete link.
    $this->assertNoIndividualTypeLink('customer/delete', 'No delete link on individual type form.');
  }

  /**
   * Test if the field UI is displayed on individual bundle.
   */
  public function testFieldsUi() {
    $user = $this->drupalCreateUser([
      'administer crm_core_individual display',
      'administer crm_core_individual form display',
      'administer crm_core_individual fields',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('admin/structure/crm-core/individual-types/customer/fields');
    $this->assertText(t('Manage fields'), 'Manage fields local task in available.');
    $this->assertText(t('Manage form display'), 'Manage form display local task in available.');
    $this->assertText(t('Manage display'), 'Manage display local task in available.');

    $this->drupalGet('admin/structure/crm-core/individual-types/customer/form-display');
    $this->assertText(t('Name'), 'Name field is available on form display.');

    $this->drupalGet('admin/structure/crm-core/individual-types/customer/display');
    $this->assertText(t('Name'), 'Name field is available on manage display.');
  }

  /**
   * Test individual revisions.
   */
  public function testIndividualRevisions() {
    $user = $this->drupalCreateUser([
      'create crm_core_individual entities',
      'view any crm_core_individual entity',
      'edit any crm_core_individual entity',
      'view all crm_core_individual revisions',
      'revert all crm_core_individual revisions',
    ]);
    $this->drupalLogin($user);

    $individual = ['name[0][given]' => 'rev', 'name[0][family]' => '1'];
    $this->drupalPostForm('crm-core/individual/add/customer', $individual, 'Save Customer');
    $individual_1 = ['name[0][family]' => '2'];
    $this->drupalPostForm('crm-core/individual/1/edit', $individual_1, 'Save Customer');
    $individual_2 = ['name[0][family]' => '3'];
    $this->drupalPostForm('crm-core/individual/1/edit', $individual_2, 'Save Customer');

    $this->clickLink('Revisions');
    $this->assertLinkByHref('crm-core/individual/1');
    $this->assertLinkByHref('crm-core/individual/1/revisions/1/view');
    $this->assertLinkByHref('crm-core/individual/1/revisions/2/view');

    $this->drupalGet('crm-core/individual/1/revisions/1/view');
    $this->assertText('rev 1');
    $this->drupalGet('crm-core/individual/1/revisions/2/view');
    $this->assertText('rev 2');

    /** @var \Drupal\crm_core_contact\ContactInterface $individual */
    $individual = Individual::create(['type' => 'customer']);
    $individual->save();

    $revision = clone $individual;
    $revision->setNewRevision(TRUE);
    $revision->isDefaultRevision(FALSE);
    $revision->save();

    $this->drupalGet($revision->toUrl('version-history'));
    // Assert we have one revision link and current revision.
    $this->assertLinkByHref('crm-core/individual/' . $individual->id() . '/revisions/5/view');
    $this->assertLinkByHref('crm-core/individual/' . $individual->id());

    // Assert we have revision revert link.
    $this->assertLinkByHref('crm-core/individual/' . $individual->id() . '/revisions/5/revert');
    $this->drupalGet('crm-core/individual/' . $individual->id() . '/revisions/5/revert');
    $this->assertResponse(200);

    // Check view revision route.
    $this->drupalGet('crm-core/individual/' . $individual->id() . '/revisions/5/view');
    $this->assertRaw('Nameless #2');
  }

  /**
   * Test list builder views for contact entities.
   */
  public function testListBuilder() {
    $user = $this->drupalCreateUser([
      'view any crm_core_individual entity',
      'view any crm_core_organization entity',
      'administer views',
    ]);
    $this->drupalLogin($user);

    // Delete created organization view to get default view from list builder.
    $this->drupalGet('admin/structure/views/view/crm_core_organization_overview/delete');
    $this->drupalPostForm(NULL, [], TRUE);
    // Check organization collection page.
    $this->drupalGet('/crm-core/organization');
    $this->assertResponse(200);
    // Delete created individual view to get default view from list builder.
    $this->drupalGet('admin/structure/views/view/crm_core_individual_overview/delete');
    $this->drupalPostForm(NULL, [], TRUE);
    // Assert response on individual collection page.
    $this->drupalGet('/crm-core/individual');
    $this->assertResponse(200);
  }

  /**
   * Asserts a individual type link.
   *
   * The path 'admin/structure/crm-core/individual-types/' gets prepended to the
   * path provided.
   *
   * @see WebTestBase::assertLinkByHref()
   */
  public function assertIndividualTypeLink($href, $message = '') {
    $this->assertLinkByHref('admin/structure/crm-core/individual-types/' . $href, 0, $message);
  }

  /**
   * Asserts no individual type link.
   *
   * The path 'admin/structure/crm-core/individual-types/' gets prepended to the
   * path provided.
   *
   * @see WebTestBase::assertNoLinkByHref()
   */
  public function assertNoIndividualTypeLink($href, $message = '') {
    $this->assertNoLinkByHref('admin/structure/crm-core/individual-types/' . $href, $message);
  }

}
