<?php
/**
 * @file
 * Contains Drupal\securesite\Tests\FormAuth\SecureSiteFormNoneTest
 */
namespace Drupal\securesite\Tests\FormAuth;

use Drupal\simpletest\WebTestBase;

/**
 * Functional test for form authentication without credentials.
 */
class SecureSiteFormNoneTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('securesite');

  /**
   * Implements getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => t('Form authentication: No credentials'),
      'description' => t('Test HTML form authentication without credentials.'),
      'group' => t('Secure Site'),
    );
  }

  /**
   * Implements setUp().
   */
  function setUp() {
    parent::setUp();
    $config = \Drupal::config('securesite.settings');
    $config->set('securesite_enabled', SECURESITE_ALWAYS);
    // Should work with all authentication methods enabled.
    $config->set('securesite_type', array(SECURESITE_FORM, SECURESITE_BASIC, SECURESITE_DIGEST));
    $config->save();
  }

  /**
   * Request home page without credentials.
   */
  function testSecureSiteTypeFormNone() {
    $this->drupalGet(NULL);
    //todo form id might change
    $this->assertFieldByXPath('//form[@id="securesite-user-login"]', '', t('Requesting home page without credentials.'));
  }
}
