<?php

namespace Drupal\Tests\japan_postal_code\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Ensure Japan postal code data fetch and web api functionality.
 *
 * @group japan_postal_code
 */
class JapanPostalCodeFunctionalTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['japan_postal_code'];

  /**
   * {@inheritdoc}
   *
   * The minimal profile is enough.
   */
  protected $profile = 'minimal';

  /**
   * The created user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $privilegedUser;

  /**
   * Implements setUp().
   */
  public function setUp() {
    parent::setUp();

    // Create and log in our privileged user.
    $this->privilegedUser = $this->drupalCreateUser([
      'configure japan postal code',
      'use japan postal code service',
    ]);
    $this->drupalLogin($this->privilegedUser);
  }

  /**
   * Ensure Japan postal code basic usage.
   */
  public function testFetchAndUsePostalCode() {

    // Ensure the table for Japan postal code data is empty.
    $count = _japan_postal_code_count_postal_code_records();
    $this->assertEqual(0, $count,
      'No record exists in japan postal code database table initially.');

    // Open admin page for Japan postal code.
    $this->drupalGet('admin/config/services/japan-postal-code');
    $this->assertResponse('200');
    $this->assertRaw(t('Japan postal code'));
    $this->assertText(t('0 records exist in the postal code database table.'));

    // Fetch postal code data from Japan post office website.
    $edit = [];
    $this->drupalPostForm('admin/config/services/japan-postal-code', $edit,
      t('Fetch and update the postal code data'));
    $this->assertText(
      t('Japan postal code database table is successfully updated.'));

    // Ensure the table for Japan postal code data is empty.
    $count = _japan_postal_code_count_postal_code_records();
    $this->assertTrue($count > 0,
      'More than zero Japan postal code data is imported.');

    // Make use of the downloaded address data through the page interface.
    // For valid postal code, successful status is responded.
    $path_prefixes = [
      'japan-postal-code/address/',
      'japan-postal-code/addresses/',
    ];
    foreach ($path_prefixes as $path_prefix) {
      $valid_postal_code = '1000001';
      $this->drupalGet($path_prefix . $valid_postal_code);
      $this->assertResponse('200');
      $this->assertNoText('false');
      $this->assertText('postal_code');
      $this->assertText('prefecture');

      $invalid_postal_code = 'abc';
      $this->drupalGet($path_prefix . $invalid_postal_code);
      $this->assertText('false');
    }
  }

}
