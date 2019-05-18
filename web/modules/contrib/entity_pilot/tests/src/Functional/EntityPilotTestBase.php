<?php

namespace Drupal\Tests\entity_pilot\Functional;

use Drupal\Component\Utility\Html;
use Drupal\entity_pilot\Entity\Account;
use Drupal\entity_pilot\Entity\Departure;
use Drupal\Tests\BrowserTestBase;

/**
 * Provides a base test class for Entity Pilot tests.
 */
abstract class EntityPilotTestBase extends BrowserTestBase {

  /**
   * Profile to use.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * Admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer entity_pilot accounts',
    'administer entity_pilot arrivals',
    'administer entity_pilot departures',
  ];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference',
    'dynamic_entity_reference',
    'entity_pilot',
    'block',
  ];

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalPlaceBlock('local_tasks_block', ['id' => 'tabs_block']);
    $this->drupalPlaceBlock('page_title_block');
    $this->drupalPlaceBlock('local_actions_block', ['id' => 'actions_block']);
  }

  /**
   * Creates an Entity Pilot account (bundle).
   *
   * @param string $label
   *   The account label.
   * @param string $carrier_id
   *   (optional) Account carrier ID.
   * @param string $black_box_key
   *   (optional) Account black box key.
   * @param string $description
   *   (optional) Account description.
   *
   * @return \Drupal\entity_pilot\AccountInterface
   *   Created Entity Pilot account.
   */
  protected function createAccount($label, $carrier_id = NULL, $black_box_key = NULL, $description = NULL) {
    $bundle = Account::create([
      'id' => mb_strtolower($label),
      'label' => $label,
      'carrierId' => $carrier_id ?: $this->randomMachineName(),
      'blackBoxKey' => $black_box_key ?: $this->randomMachineName(),
      'description' => $description ?: $this->randomMachineName(),
      'legacy_secret' => "a22a0b2884fd73c4e211d68e1f031051",
      'secret' => 'def00000cc8d032c4b4c13cb18c6760dd486497ab9bb054e0674e567ef772611938503bde6cfb17c3d89a60c413cacb8137239459187b2b44109f4fc1b03e9205184cd15',
    ]);
    $bundle->save();
    return $bundle;
  }

  /**
   * Creates a departure flight.
   *
   * @param string|bool $info
   *   (optional) Brief name of flight. Defaults to FALSE.
   *   Defaults to FALSE.
   * @param string $bundle
   *   (optional) Bundle name. Defaults to 'primary'.
   *
   * @return \Drupal\entity_pilot\DepartureInterface
   *   Created flight.
   */
  protected function createDeparture($info = FALSE, $bundle = 'primary') {
    if ($flight = Departure::create([
      'info' => $info ?: $this->randomMachineName(),
      'account' => $bundle,
      'langcode' => 'en',
    ])) {
      $flight->save();
    }
    return $flight;
  }

  /**
   * Checks for meta refresh tag and if found call drupalGet() recursively.
   *
   * This function looks for the http-equiv attribute to be set to "Refresh" and
   * is case-sensitive.
   *
   * @param int|null $max_count
   *   Maximum number of refreshes. NULL for unlimited.
   * @param int $count
   *   Current refresh count.
   */
  protected function checkForMetaRefresh($max_count = NULL, $count = 0) {
    if ($max_count && $count > $max_count) {
      return;
    }
    if ($refresh = $this->xpath('//meta[@http-equiv="Refresh"]')) {
      // Parse the content attribute of the meta tag for the format:
      // "[delay]: URL=[page_to_redirect_to]".
      if (preg_match('/\d+;\s*URL=(?<url>.*)/i', $refresh[0]->getAttribute('content'), $match)) {
        $this->drupalGet($this->getAbsoluteUrl(Html::decodeEntities($match['url'])));
        $this->checkForMetaRefresh($max_count, $count + 1);
      }
    }
  }

}
