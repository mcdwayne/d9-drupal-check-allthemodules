<?php

/**
 * @file
 * Test cases for Content Entity Example Module.
 */

namespace Drupal\impression\Tests;

use Drupal\impression\Entity\Base;
use Drupal\examples\Tests\ExamplesTestBase;

/**
 * Tests the basic functions of the Content Entity Example module.
 *
 * @package Drupal\impression\Tests
 *
 * @ingroup impression
 *
 * @group impression
 * @group examples
 */
class ContentEntityExampleTest extends ExamplesTestBase {

  public static $modules = array('impression', 'block', 'field_ui');

  /**
   * Basic tests for Content Entity Example.
   */
  public function testContentEntityExample() {
    $web_user = $this->drupalCreateUser(array(
      'add base entity',
      'edit base entity',
      'view base entity',
      'delete base entity',
      'administer base entity',
      'administer impression_base display',
      'administer impression_base fields',
      'administer impression_base form display'));

    // Anonymous User should not see the link to the listing.
    $this->assertNoText(t('Content Entity Example: Bases Listing'));

    $this->drupalLogin($web_user);

    // Web_user user has the right to view listing.
    $this->assertLink(t('Content Entity Example: Bases Listing'));

    $this->clickLink(t('Content Entity Example: Bases Listing'));

    // WebUser can add entity content.
    $this->assertLink(t('Add Base'));

    $this->clickLink(t('Add Base'));

    $this->assertFieldByName('name[0][value]', '', 'Name Field, empty');
    $this->assertFieldByName('name[0][value]', '', 'First Name Field, empty');
    $this->assertFieldByName('name[0][value]', '', 'Gender Field, empty');

    $user_ref = $web_user->name->value . ' (' . $web_user->id() . ')';
    $this->assertFieldByName('user_id[0][target_id]', $user_ref, 'User ID reference field points to web_user');

    // Post content, save an instance. Go back to list after saving.
    $edit = array(
      'name[0][value]' => 'test name',
      'first_name[0][value]' => 'test first name',
      'gender' => 'male',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Entity listed.
    $this->assertLink(t('Edit'));
    $this->assertLink(t('Delete'));

    $this->clickLink('test name');

    // Entity shown.
    $this->assertText(t('test name'));
    $this->assertText(t('test first name'));
    $this->assertText(t('male'));
    $this->assertLink(t('Add Base'));
    $this->assertLink(t('Edit'));
    $this->assertLink(t('Delete'));

    // Delete the entity.
    $this->clickLink('Delete');

    // Confirm deletion.
    $this->assertLink(t('Cancel'));
    $this->drupalPostForm(NULL, array(), 'Delete');

    // Back to list, must be empty.
    $this->assertNoText('test name');

    // Settings page.
    $this->drupalGet('admin/structure/impression_base_settings');
    $this->assertText(t('Base Settings'));

    // Make sure the field manipulation links are available.
    $this->assertLink(t('Settings'));
    $this->assertLink(t('Manage fields'));
    $this->assertLink(t('Manage form display'));
    $this->assertLink(t('Manage display'));
  }

  /**
   * Test all paths exposed by the module, by permission.
   */
  public function testPaths() {
    // Generate a base so that we can test the paths against it.
    $base = Base::create(
      array(
        'name' => 'somename',
        'first_name' => 'Joe',
        'gender' => 'female',
      )
    );
    $base->save();

    // Gather the test data.
    $data = $this->providerTestPaths($base->id());

    // Run the tests.
    foreach ($data as $datum) {
      // drupalCreateUser() doesn't know what to do with an empty permission
      // array, so we help it out.
      if ($datum[2]) {
        $user = $this->drupalCreateUser(array($datum[2]));
        $this->drupalLogin($user);
      }
      else {
        $user = $this->drupalCreateUser();
        $this->drupalLogin($user);
      }
      $this->drupalGet($datum[1]);
      $this->assertResponse($datum[0]);
    }
  }

  /**
   * Data provider for testPaths.
   *
   * @param int $base_id
   *   The id of an existing Base entity.
   *
   * @return array
   *   Nested array of testing data. Arranged like this:
   *   - Expected response code.
   *   - Path to request.
   *   - Permission for the user.
   */
  protected function providerTestPaths($base_id) {
    return array(
      array(
        200,
        '/impression_base/' . $base_id,
        'view base entity',
      ),
      array(
        403,
        '/impression_base/' . $base_id,
        '',
      ),
      array(
        200,
        '/impression_base/list',
        'view base entity',
      ),
      array(
        403,
        '/impression_base/list',
        '',
      ),
      array(
        200,
        '/impression_base/add',
        'add base entity',
      ),
      array(
        403,
        '/impression_base/add',
        '',
      ),
      array(
        200,
        '/impression_base/' . $base_id . '/edit',
        'edit base entity',
      ),
      array(
        403,
        '/impression_base/' . $base_id . '/edit',
        '',
      ),
      array(
        200,
        '/base/' . $base_id . '/delete',
        'delete base entity',
      ),
      array(
        403,
        '/base/' . $base_id . '/delete',
        '',
      ),
      array(
        200,
        'admin/structure/impression_base_settings',
        'administer base entity',
      ),
      array(
        403,
        'admin/structure/impression_base_settings',
        '',
      ),
    );
  }

}
