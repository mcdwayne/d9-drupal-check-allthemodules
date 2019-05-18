<?php

namespace Drupal\Tests\admin_language_negotiation\Functional;

use Drupal\admin_language_negotiation\Plugin\LanguageNegotiation\AdminLanguageNegotiationUserPermission;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests base class.
 *
 * @group admin_language_negotiation
 */
abstract class AdminLanguageNegotiationTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['admin_language_negotiation'];

  /**
   * An admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * @inheritdoc
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['administer languages', 'administer users']);
  }

  /**
   * @return array
   */
  protected function getActiveLanguages() {
    $languages = (array) \Drupal::service('language_manager')->getLanguages();
    $displayLanguages = [];
    /** @var \Drupal\Core\Language\Language $lang */
    foreach ($languages as $lang) {
      $displayLanguages[$lang->getId()] = $lang->getName();
    }
    return $displayLanguages;
  }

  /**
   * Enables the negotiation force in the Drupal language detection page.
   */
  protected function enableNegotiation($adminUser) {
    $this->drupalLogin($adminUser);

    $this->drupalGet('/admin/config/regional/language/detection');
    $this->assertSession()->statusCodeEquals(200);

    $adminLanguagePermissionId = AdminLanguageNegotiationUserPermission::METHOD_ID;
    $edit = [
      "language_interface[enabled][{$adminLanguagePermissionId}]" => 1,
      "language_interface[weight][{$adminLanguagePermissionId}]"  => -20,
    ];

    $this->drupalPostForm(NULL, $edit, t('Save settings'));

    $this->assertSession()->responseContains('Language detection configuration saved.');
    $this->drupalLogout();
  }

}
