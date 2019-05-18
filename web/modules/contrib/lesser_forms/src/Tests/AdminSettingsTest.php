<?php
/**
 * @file
 *
 * Contains Drupal\Tests\lesser_forms\Functional\AdminSettingsTest.
 */

namespace Drupal\lesser_forms\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Class AdminSettingsTest
 *
 * @package Drupal\Tests\lesser_forms\Functional
 * @group lesser_forms
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class AdminSettingsTest extends WebTestBase {

  /**
   * @var \Drupal\user\Entity\User.
   */
  protected $user;

  /**
   * Lesser Forms path.
   * @var string
   */
  private static $lf_path = 'admin/config/content/lesser_forms';

  /**
   * Enable modules.
   */
  public static $modules = ['lesser_forms'];

  /**
   * Get Info for Simpletest.
   * @return array
   */
  public static function getInfo() {
    // Note: getInfo() strings should not be translated.
    return array(
      'name' => 'Lesser Forms',
      'description' => 'Tests to make sure Lesser Forms works correctly.',
      'group' => 'Lesser Forms',
    );

  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp(self::$modules);

    $permissions = array('administer lesser forms');
    $this->user = $this->drupalCreateUser($permissions, 'lesser_forms_admin', TRUE);
    $this->drupalLogin($this->user);
  }

  /**
   * Test if we can reach the path.
   */
  public function testGetForm() {
    $this->drupalGet(self::$lf_path);
    $this->assertResponse(200);
  }

  /**
   * Test if we can submit the form.
   * @throws \Exception
   */
  public function testSaveForm() {
    $this->drupalPostForm(self::$lf_path, array(), 'Save config');
    $this->assertText(t('The configuration options have been saved.'));
  }


}
