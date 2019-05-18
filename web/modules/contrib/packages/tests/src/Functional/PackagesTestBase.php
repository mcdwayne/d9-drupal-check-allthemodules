<?php

namespace Drupal\Tests\packages\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Base class for Packages functional tests.
 */
abstract class PackagesTestBase extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'packages',
    'packages_example_login_greeting',
    'packages_example_page',
  ];

  /**
   * A user with packages access.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $packagesUser;

  /**
   * A user with packages access plus extra permissions for any custom packages.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $packagesExtraUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a packages user.
    $this->packagesUser = $this->drupalCreateUser(['access packages']);

    // Create a packages user that can also access the example page.
    // package as well.
    $this->packagesExtraUser = $this->drupalCreateUser(['access packages', 'access packages example page']);
  }

  /**
   * Submit the packages form.
   *
   * @param array $package_ids
   *   An array keyed by package Id with a value of their status.
   */
  public function submitPackagesForm(array $package_ids) {
    // Build the form data to submit.
    $edit = [];
    foreach ($package_ids as $id => $status) {
      $edit["packages[{$id}][enabled]"] = $status;
    }

    // Submit the form.
    $this->drupalPostForm('/packages', $edit, $this->t('Save'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Your packages have been updated successfully.');
  }

}
