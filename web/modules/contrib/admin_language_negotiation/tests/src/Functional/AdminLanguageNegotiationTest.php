<?php

namespace Drupal\Tests\admin_language_negotiation\Functional;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the basic functionality provided by the Language Negotiation User Permission plugin.
 *
 * @group admin_language_negotiation
 */
class AdminLanguageNegotiationTest extends AdminLanguageNegotiationTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user'];

  /**
   * A user without the permission to administer its admin language.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $userWithoutPermission;

  /**
   * A user with the permission to administer its admin language.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $userWithPermission;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    ConfigurableLanguage::createFromLangcode('it')->save();
    ConfigurableLanguage::createFromLangcode('de')->save();

    $this->userWithoutPermission = $this->drupalCreateUser(['administer users']);
    $this->userWithPermission = $this->drupalCreateUser(['admin_language_negotiation detection', 'administer users']);

    $this->enableNegotiation($this->adminUser);
  }

  /**
   * Tests a user without a proper permission.
   */
  public function testUserWithoutPermissionCannotSeeAdministrationLanguageSwitcher() {
    $this->drupalLogin($this->userWithoutPermission);
    $this->drupalGet('user/' . $this->userWithoutPermission->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->responseNotContains('Administration pages language');
  }

  /**
   * Tests a user with the right permission.
   */
  public function testUserWithPermissionCanSeeAndEditAdministrationLanguageSwitcher() {
    $randomLanguage = array_rand($this->getActiveLanguages());

    $this->drupalLogin($this->userWithPermission);
    $this->drupalGet('user/' . $this->userWithPermission->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Administration pages language');
    $this->assertSession()->fieldExists('preferred_admin_langcode' );

    $edit = [
      'preferred_admin_langcode' => $randomLanguage,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('user/' . $this->userWithPermission->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Administration pages language');
    $this->assertSession()->fieldValueEquals('preferred_admin_langcode', $randomLanguage);
  }

}
