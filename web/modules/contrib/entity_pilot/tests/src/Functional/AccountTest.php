<?php

namespace Drupal\Tests\entity_pilot\Functional;

use Drupal\Core\Url;
use Drupal\entity_pilot\Entity\Account;

/**
 * Ensures that Entity Pilot account functions work correctly.
 *
 * @group entity_pilot
 */
class AccountTest extends EntityPilotTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field_ui',
    'dynamic_entity_reference',
    'hal',
    'serialization',
    'rest',
    'entity_pilot',
    // @todo remove when https://www.drupal.org/node/2308745 lands
    'node',
  ];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer entity_pilot accounts',
    'administer ep_departure fields',
    'administer entity_pilot departures',
  ];

  /**
   * Tests creating an account programmatically and via a form.
   */
  public function testAccountCreation() {
    // Create an account programmaticaly.
    $this->createAccount('other');

    $account = entity_load('ep_account', 'other');
    $this->assertTrue($account, 'The new account has been created.');

    // Login a test user.
    $this->drupalLogin($this->adminUser);

    // Create an account via the user interface.
    $edit = [
      'id' => 'foo',
      'label' => 'title for foo',
      'carrierId' => '123456',
      'blackBoxKey' => '45678',
      'secret' => "a22a0b2884fd73c4e211d68e1f031051",
    ];
    $this->drupalPostForm('/admin/structure/entity-pilot/accounts/accounts/add', $edit, t('Save'));
    $account = entity_load('ep_account', 'foo');
    $this->assertTrue($account, 'The new account has been created.');
  }

  /**
   * Tests editing an account using the UI.
   */
  public function testAccountEditing() {
    $this->drupalLogin($this->adminUser);
    $this->createAccount('primary');

    // Verify that title and body fields are displayed.
    $this->drupalGet('admin/structure/entity-pilot/departures/add/primary');
    $this->assertRaw('Passengers', 'Passengers field was found.');
    // Change the account name.
    $edit = [
      'label' => 'Bar',
    ];
    $this->drupalPostForm('admin/structure/entity-pilot/accounts/manage/primary', $edit, t('Save'));

    $this->drupalGet('admin/structure/entity-pilot/accounts');
    $this->assertRaw('Bar', 'New name was displayed.');
    $this->clickLink('Edit');
    $this->assertContains(Url::fromRoute('entity.ep_account.edit_form', ['ep_account' => 'primary'])->toString(), $this->getUrl(), 'Original machine name was used in URL.');
  }

  /**
   * Tests deleting an account that still has flights.
   */
  public function testAccountDeletion() {
    // Create an account programmatically.
    $account = $this->createAccount('foo');

    $this->drupalLogin($this->adminUser);

    // Add a new flight of this type.
    if ($flight = $this->createDeparture(FALSE, 'foo')) {
      // Attempt to delete the account, which should not be allowed.
      $this->drupalGet('admin/structure/entity-pilot/accounts/manage/' . $account->id() . '/delete');
      $this->assertRaw(
        t('%label is used by 1 flight on your site. You can not remove this account until you have removed all of the associated flights.', ['%label' => $account->label()]),
        'The account will not be deleted until all flights for that account are removed.'
      );
      $this->assertNoText(t('This action cannot be undone.'), 'The deletion form is not available.');

      // Delete the flight.
      $flight->delete();
      // Attempt to delete the account, which should now be allowed.
      $this->drupalGet('admin/structure/entity-pilot/accounts/manage/' . $account->id() . '/delete');
      $this->assertRaw(
        t('Are you sure you want to delete the account named %account?', ['%account' => $account->id()]),
        'The account is available for deletion.'
      );
      $this->assertText(t('This action cannot be undone.'), 'The account deletion confirmation form is available.');
      $this->drupalPostForm(NULL, [], t('Confirm'));
      \Drupal::entityManager()->getStorage('ep_account')->resetCache([$account->id()]);
      $account = Account::load($account->id());
      $this->assertFalse($account);
    }
  }

}
