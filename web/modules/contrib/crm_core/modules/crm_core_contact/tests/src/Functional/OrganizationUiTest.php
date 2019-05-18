<?php

namespace Drupal\Tests\crm_core_contact\Functional;

use Drupal\crm_core_contact\Entity\Organization;
use Drupal\crm_core_contact\Entity\OrganizationType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the UI for Organization CRUD operations.
 *
 * @group crm_core_contact
 */
class OrganizationUiTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'crm_core_contact',
    'crm_core_activity',
    'crm_core_tests',
    'block',
    'datetime',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    OrganizationType::create([
      'label' => 'Supplier',
      'id' => 'supplier',
      'description' => 'A person or company that supplies goods or services.',
      'primary_fields' => [],
    ])->save();

    OrganizationType::create([
      'label' => 'Household',
      'id' => 'household',
      'description' => 'A collection of individuals generally located at the same residence.',
      'primary_fields' => [],
    ])->save();

    // Place local actions and local task blocks.
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests the organization operations.
   *
   * User with permissions 'administer crm_core_organization entities' should
   * be able to create/edit/delete organizations of any organization type.
   */
  public function testOrganizationOperations() {
    // Create user and login.
    $user = $this->drupalCreateUser([
      'administer crm_core_organization entities',
      'administer organization types',
      'create crm_core_organization entities of bundle supplier',
      'view any crm_core_organization entity',
      'view any crm_core_activity entity',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('crm-core');
    $this->assertSession()->linkExists(t('CRM Organizations'));
    $this->clickLink(t('CRM Organizations'));
    // There should be no organizations available after fresh installation and
    // there is a link to create new organizations.
    $this->assertText(t('There are no organizations available.'), 'No organizations available after fresh installation.');
    $this->assertSession()->linkExists(t('Add an organization'));

    $household_values = [
      'name[0][value]' => 'Fam. Johnson',
    ];
    $this->drupalPostForm('crm-core/organization/add/household', $household_values, 'Save Household');

    // Assert we were redirected back to the list of contacts.
    $this->assertUrl('crm-core/organization');

    $this->assertSession()->pageTextContains('Fam. Johnson');
    $this->assertSession()->pageTextContains('Household');

    $households = \Drupal::entityTypeManager()->getStorage('crm_core_organization')->loadByProperties(['name' => 'Fam. Johnson']);
    $household = current($households);

    $household_values = [
      'name[0][value]' => 'Fam. Bane',
    ];

    $this->drupalPostForm('crm-core/organization/' . $household->id() . '/edit', $household_values, 'Save Household');

    // Assert we are viewing the entity.
    $this->assertUrl('crm-core/organization/' . $household->id());
    $this->assertSession()->pageTextContains('Fam. Bane');

    // Assert organization template has been used.
    $this->assertRaw('Fam. Bane</div>');

    // Check listing page.
    $this->drupalGet('crm-core/organization');
    $this->assertSession()->pageTextContains('Fam. Bane');

    // Create Supplier organization.
    $supplier_values = [
      'name[0][value]' => 'Example ltd',
    ];
    $this->drupalPostForm('crm-core/organization/add/supplier', $supplier_values, 'Save Supplier');
    // Create supplier with no name.
    $this->drupalPostForm('crm-core/organization/add/supplier', [], 'Save Supplier');

    // Assert we were redirected back to the list of organizations.
    $this->assertUrl('crm-core/organization');

    $this->assertSession()->linkExists('Example ltd');
    $this->assertSession()->linkExists('Example ltd', 0, 'Newly created organization title listed.');
    $this->assertSession()->linkExists('Nameless #3', 0, 'Nameless organization title listed.');
    $this->assertSession()->pageTextContains('Supplier');

    // Assert all view headers are available.
    $this->assertSession()->linkExists('Name');
    $this->assertSession()->linkExists('Organization type');
    $this->assertSession()->linkExists('Updated');
    $this->assertSession()->pageTextContains('Operations');

    $labels = $this->xpath('//form[@class="views-exposed-form"]/div/div/label[text()="Type"]');
    $this->assertEquals(1, count($labels), 'Individual type is an exposed filter.');

    $labels = $this->xpath('//form[@class="views-exposed-form"]/div/div/label[text()="Name"]');
    $this->assertEquals(1, count($labels), 'Name is an exposed filter.');

    $organizations = \Drupal::entityTypeManager()->getStorage('crm_core_organization')->loadByProperties(['name' => 'Example ltd']);
    $organization = current($organizations);

    // Create another user.
    $new_user = $this->drupalCreateUser();

    // Test EntityOwnerTrait functions on organization.
    $this->assertEqual($organization->getOwnerId(), $user->id());
    $this->assertEqual($organization->getOwner()->id(), $user->id());
    $organization->setOwner($new_user);
    $this->assertEqual($organization->getOwnerId(), $new_user->id());
    $this->assertEqual($organization->getOwner()->id(), $new_user->id());
    $organization->setOwnerId($user->id());
    $this->assertEqual($organization->getOwnerId(), $user->id());
    $this->assertEqual($organization->getOwner()->id(), $user->id());

    $this->assertSession()->responseContains('crm-core/organization/' . $organization->id() . '/edit');
    $this->assertSession()->responseContains('crm-core/organization/' . $organization->id() . '/delete');

    // Organization updated date is available.
    $this->assertSession()->pageTextContains($this->container->get('date.formatter')->format($organization->get('changed')->value, 'short'));

    // Edit operation.
    $supplier_values = [
      'name[0][value]' => 'Another Example ltd',
    ];
    $this->drupalPostForm('crm-core/organization/' . $organization->id() . '/edit', $supplier_values, 'Save Supplier');

    // Assert we are viewing the entity.
    $this->assertUrl('crm-core/organization/' . $organization->id());
    $this->assertSession()->pageTextContains('Another Example ltd');

    $this->drupalGet('crm-core/organization/1/edit');
    // Local task "Delete" is available.
    $this->assertSession()->responseContains('data-drupal-link-system-path="crm-core/organization/1/delete"');
    // Delete link is available.
    $this->assertSession()->responseContains('crm-core/organization/1/delete" class="button button--danger" data-drupal-selector="edit-delete" id="edit-delete"');

    // Check listing page.
    $this->drupalGet('crm-core/organization');
    $this->assertSession()->linkExists('Another Example ltd', 0, 'Updated organization title listed.');

    // Delete organizations.
    $this->drupalPostForm('crm-core/organization/1/delete', [], t('Delete'));
    $this->drupalPostForm('crm-core/organization/2/delete', [], t('Delete'));
    $this->drupalPostForm('crm-core/organization/3/delete', [], t('Delete'));
    $this->assertUrl('crm-core/organization');
    $this->assertSession()->linkNotExists('Another Example ltd', 0, 'Deleted organization title no more listed.');

    // Assert that there are no organizations.
    $this->assertSession()->pageTextContains('There are no organizations available.');
  }

  /**
   * Tests the organization type operations.
   *
   * User with permissions 'administer organization types' should be able to
   * create/edit/delete organization types.
   */
  public function testOrganizationTypeOperations() {
    // Create user with permission 'administer organization types'.
    $user = $this->drupalCreateUser(['administer organization types']);
    $this->drupalLogin($user);

    $this->drupalGet('admin/structure/crm-core/organization-types');

    // Test that there are edit, delete links for existing organizations.
    $this->assertOrganizationTypeLink('supplier', 'Edit link for supplier.');
    $this->assertOrganizationTypeLink('supplier/delete', 'Delete link for supplier.');

    $this->assertOrganizationTypeLink('household', 'Edit link for household.');
    $this->assertOrganizationTypeLink('household/delete', 'Delete link for household.');

    // Add another organization type.
    $second_organization_type = OrganizationType::create([
      'id' => 'new_organization_type',
      'label' => 'New organization type',
      'primary_fields' => [],
    ]);
    $second_organization_type->save();

    $this->drupalGet('admin/structure/crm-core/organization-types');

    // Create organization of type 'supplier.'.
    Organization::create(['type' => 'supplier'])->save();

    $this->drupalGet('admin/structure/crm-core/organization-types');

    // Test that there is no a delete link.
    $this->assertNoOrganizationTypeLink('supplier/delete', 'No delete link for supplier.');
    $this->drupalGet('admin/structure/crm-core/organization-types/supplier/delete');
    $this->assertResponse(403);

    $this->drupalGet('admin/structure/crm-core/organization-types/supplier');

    // Test that there is no a delete link on supplier type form.
    $this->assertNoOrganizationTypeLink('supplier/delete', 'No delete link on supplier type form.');
  }

  /**
   * Test if the field UI is displayed on organization bundle.
   */
  public function testFieldsUi() {
    $user = $this->drupalCreateUser([
      'administer crm_core_organization display',
      'administer crm_core_organization form display',
      'administer crm_core_organization fields',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('admin/structure/crm-core/organization-types/supplier/fields');

    $this->assertSession()->pageTextContains('Manage fields');
    $this->assertSession()->pageTextContains('Manage form display');
    $this->assertSession()->pageTextContains('Manage display');

    $this->drupalGet('admin/structure/crm-core/organization-types/supplier/form-display');
    $this->assertSession()->pageTextContains('Name');

    $this->drupalGet('admin/structure/crm-core/organization-types/supplier/display');
    $this->assertSession()->pageTextContains('Name');
  }

  /**
   * Test organization revisions.
   */
  public function testOrganizationRevisions() {
    $user = $this->drupalCreateUser([
      'administer crm_core_organization entities',
      'view all crm_core_organization revisions',
    ]);
    $this->drupalLogin($user);

    $organization = ['name[0][value]' => 'rev'];
    $this->drupalPostForm('crm-core/organization/add/supplier', $organization, 'Save Supplier');
    $organization_1 = ['name[0][value]' => 'rev1'];
    $this->drupalPostForm('crm-core/organization/1/edit', $organization_1, 'Save Supplier');
    $organization_2 = ['name[0][value]' => 'rev2'];
    $this->drupalPostForm('crm-core/organization/1/edit', $organization_2, 'Save Supplier');

    $this->clickLink('Revisions');
    $this->assertLinkByHref('crm-core/organization/1');
    $this->assertLinkByHref('crm-core/organization/1/revisions/1/view');
    $this->assertLinkByHref('crm-core/organization/1/revisions/2/view');

    $this->drupalGet('crm-core/organization/1/revisions/1/view');
    $this->assertSession()->pageTextContains('rev');
    $this->drupalGet('crm-core/organization/1/revisions/2/view');
    $this->assertSession()->pageTextContains('rev1');
  }

  /**
   * Asserts an organization type link.
   *
   * The path 'admin/structure/crm-core/organization-types/' gets prepended to
   * the path provided.
   *
   * @see WebTestBase::assertLinkByHref()
   */
  public function assertOrganizationTypeLink($href, $message = '') {
    $this->assertSession()->linkByHrefExists('admin/structure/crm-core/organization-types/' . $href, 0, $message);
  }

  /**
   * Asserts no organization type link.
   *
   * The path 'admin/structure/crm-core/organization-types/' gets prepended to
   * the path provided.
   *
   * @see WebTestBase::assertNoLinkByHref()
   */
  public function assertNoOrganizationTypeLink($href, $message = '') {
    $this->assertSession()->linkByHrefNotExists('admin/structure/crm-core/organization-types/' . $href, $message);
  }

}
