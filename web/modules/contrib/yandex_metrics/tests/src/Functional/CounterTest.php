<?php

/**
 * @file
 * Contains \Drupal\yandex_metrics\Tests\CounterTest.
 */

namespace Drupal\Tests\yandex_metrics\Functional;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Utility\Random;

/**
 * Tests of functionality and settings of Yandex.Metrics Counter module.
 *
 * @group yandex_metrics
 */
class CounterTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('node', 'views', 'yandex_metrics');

  /**
   * Simple counter code.
   * @var string
   */
  protected $yandex_metrics_code = '';

  /**
   * Admin user
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin_user;

  /**
   * Regular user
   *
   * @var \Drupal\user\UserInterface
   */
  protected $regular_user;

  /**
   * Test case presets.
   *
   * @return bool|void
   */
  protected function setUp() {

    parent::setUp();

    $random = new Random();

    // Set simple string as counter code.
    $this->yandex_metrics_code = $random->name(8);

    // Create admin user.
    $admin_user_permissions = array(
      'administer Yandex.Metrics settings',
      'access administration pages',
      'access content overview'
    );
    $this->admin_user = $this->drupalCreateUser($admin_user_permissions);

    // Create regular user.
    $regular_user_permissions = array(
      'access content',
    );
    $this->regular_user = $this->drupalCreateUser($regular_user_permissions);

    // Create page content type to be able to create pages in test cases.
    $this->createContentType(['type' => 'page']);
  }

  /**
   * Try to find Yandex.Metrics counter code on current page.
   *
   * @return bool
   */
  protected function checkCounter() {
    return $this->assertPattern('@<div class="ym-counter">' . $this->yandex_metrics_code . '</div>@', 'The given page has the Yandex.Metrics counter.');
  }

  /**
   * Try not to find Yandex.Metrics counter code on current page.
   *
   * @return bool
   */
  protected function checkNoCounter() {
    return $this->assertNoPattern('@<div class="ym-counter">' . $this->yandex_metrics_code . '</div>@', 'The given page does not have the Yandex.Metrics counter.');
  }

  /**
   * Test counter code with default module settings.
   */
  public function testCounterCodeDefault() {

    // Login as administrator.
    $this->drupalLogin($this->admin_user);

    $edit = array();
    $edit["counter_code"] = $this->yandex_metrics_code;
    $this->drupalPostForm('admin/config/system/yandex_metrics', $edit, t('Save configuration'));

    $this->drupalLogout();

    // Anonymous user.

    // Front page.
    $this->drupalGet('node');
    $this->checkCounter();

    // 404 page.
    $this->drupalGet('404');
    $this->checkCounter();

    // Administration page.
    $this->drupalGet('admin');
    $this->checkNoCounter();

    // Login as administrator.
    $this->drupalLogin($this->admin_user);

    // Front page.
    $this->drupalGet('node');
    $this->checkCounter();

    // Administration page.
    $this->drupalGet('admin/content');
    $this->checkNoCounter();
  }

  /**
   * Test Yandex.Metrics page specific settings.
   */
  public function testCounterCodePagesSettings() {
    // Login as administrator.
    $this->drupalLogin($this->admin_user);

    $edit = array();
    $edit["counter_code"] = $this->yandex_metrics_code;
    $this->drupalPostForm('admin/config/system/yandex_metrics', $edit, t('Save configuration'));

    // Create test node.
    $node = $this->drupalCreateNode();
    // Check if counter exists.
    $this->drupalGet('node');
    $this->checkCounter();
    $this->drupalGet('node/' . $node->id());
    $this->checkCounter();

    // Disable counter on node overview and node full pages.
    $edit = array();
    $edit["pages"] = \Drupal::config('yandex_metrics.settings')->get('visibility.path.pages') .
      "\r\n" . 'node' . "\r\n" . 'node/*';
    $this->drupalPostForm('admin/config/system/yandex_metrics', $edit, t('Save configuration'));
    // Check if counter doesn't exist.
    $this->drupalGet('node');
    $this->checkNoCounter();
    $this->drupalGet('node/' . $node->id());
    $this->checkNoCounter();

    // Enable counter only on node pages.
    $edit = array();
    $edit['visibility'] = 1;
    $edit['pages'] = 'node/*';
    $this->drupalPostForm('admin/config/system/yandex_metrics', $edit, t('Save configuration'));
    // Check if counter exists.
    $this->drupalGet('node/' . $node->id());
    $this->checkCounter();
    // Check if counter doesn't exist.
    $this->drupalGet('node');
    $this->checkNoCounter();
    $this->drupalGet('user');
    $this->checkNoCounter();
  }

  /**
   * Test Yandex.Metrics role specific settings.
   */
  public function testCounterCodeRolesSettings() {

    // Login as administrator.
    $this->drupalLogin($this->admin_user);

    // Add Yandex.Metrica counter code only for anonymous users.
    $edit = array();
    $edit["counter_code"] = $this->yandex_metrics_code;
    $edit['roles[' . AccountInterface::ANONYMOUS_ROLE . ']'] = AccountInterface::ANONYMOUS_ROLE;
    $this->drupalPostForm('admin/config/system/yandex_metrics', $edit, t('Save configuration'));
    // Check if counter doesn't exist for admin.
    $this->drupalGet('node');
    $this->checkNoCounter();
    // Check if counter exists for anonymous user.
    $this->drupalLogout();
    $this->drupalGet('node');
    $this->checkCounter();
    // Check if counter doesn't exist for normal user.
    $this->drupalLogin($this->regular_user);
    $this->drupalGet('node');
    $this->checkNoCounter();

    // Add Yandex.Metrics counter code for all roles except administrators.
    $this->drupalLogin($this->admin_user);

    $admin_role_name = array_values(array_diff(
      $this->admin_user->getRoles(),
      array(AccountInterface::ANONYMOUS_ROLE, AccountInterface::AUTHENTICATED_ROLE)
    ))[0];

    $edit = array();
    $edit['visibility_roles'] = 1;
    $edit['roles[' . $admin_role_name . ']'] = $admin_role_name;
    // Unset previous setting for anonymous role.
    $edit['roles[' . AccountInterface::ANONYMOUS_ROLE . ']'] = FALSE;
    $this->drupalPostForm('admin/config/system/yandex_metrics', $edit, t('Save configuration'));
    // Check if counter doesn't exist for admin.
    $this->drupalGet('node');
    $this->checkNoCounter();
    // Check if counter exists for anonymous user.
    $this->drupalLogout();
    $this->drupalGet('node');
    $this->checkCounter();
    // Check if counter exists for normal user.
    $this->drupalLogin($this->regular_user);
    $this->drupalGet('node');
    $this->checkCounter();
  }
}
