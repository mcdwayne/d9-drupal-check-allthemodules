<?php

namespace Drupal\Tests\aggrid\Functional;

use Drupal\aggrid\Entity\Aggrid;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the aggrid module.
 *
 * @group aggrid
 *
 * @ingroup aggrid
 */
class AggridTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['aggrid', 'aggrid_demo'];

  /**
   * The installation profile to use with this test.
   *
   * We need the 'minimal' profile in order to make sure the Tool block is
   * available.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * Various functional test of the aggrid module.
   *
   * 1) Verify that the aggrid_demo_vc entity was created when the aggrid module
   * was installed.
   *
   * 2) Verify that permissions are applied to the various defined paths.
   *
   * 3) Verify that we can manage entities through the user interface.
   *
   * 4) Verify that the entity we add can be re-edited.
   *
   * 5) Verify that the label is shown in the list.
   */
  public function testAggrid() {
    $assert = $this->assertSession();

    // 1) Verify that the aggrid_demo_vc entity was created when the module was
    // installed.
    $entity = Aggrid::load('aggrid_demo_vc');
    $this->assertNotNull($entity
      , 'aggrid_demo_vc was created during installation.');

    // 2) Verify that permissions are applied to the various defined paths.
    // Define some paths. Since the aggrid_demo_vc entity is defined, we can
    // use it in our management paths.
    $forbidden_paths = [
      '/admin/structure/aggrid',
      '/admin/structure/aggrid/add',
      '/admin/structure/aggrid/aggrid_demo_vc/edit',
      '/admin/structure/aggrid/aggrid_demo_vc/delete',
    ];
    // Check each of the paths to make sure we don't have access. At this point
    // we haven't logged in any users, so the client is anonymous.
    foreach ($forbidden_paths as $path) {
      $this->drupalGet($path);
      $assert->statusCodeEquals(403);
    }

    // Create a user with no permissions.
    $noperms_user = $this->drupalCreateUser();
    $this->drupalLogin($noperms_user);
    // Should be the same result for forbidden paths, since the user needs
    // special permissions for these paths.
    foreach ($forbidden_paths as $path) {
      $this->drupalGet($path);
      $assert->statusCodeEquals(403);
    }

    // Create a user who can administer aggrid.
    $admin_user = $this->drupalCreateUser(['administer aggrid config entities']);
    $this->drupalLogin($admin_user);
    // Forbidden paths aren't forbidden any more.
    foreach ($forbidden_paths as $unforbidden) {
      $this->drupalGet($unforbidden);
      $assert->statusCodeEquals(200);
    }

    // Now that we have the admin user logged in, check the menu links.
    $this->drupalGet('');
    $assert->linkByHrefExists('aggrid');

    // 3) Verify that we can manage entities through the user interface.
    // We still have the admin user logged in, so we'll create, update, and
    // delete an entity.
    // Go to the list page.
    $this->drupalGet('/admin/structure/aggrid');
    $this->clickLink('Add ag-Grid Config Entity');
    $aggrid_label = 'Test AG Table';
    $aggrid_machine_name = 'test_ag_table';
    $this->drupalPostForm(
      NULL,
      [
        'label' => $aggrid_label,
        'id' => $aggrid_machine_name,
        'aggridDefault' => '{}',
        'addOptions' => '{}',
      ],
      'Save'
    );

    // 4) Verify that our test ag table appears when we edit it.
    $this->drupalGet('/admin/structure/aggrid/' . $aggrid_machine_name .'/edit');
    $assert->fieldExists('label');
    $assert->fieldExists('aggridDefault');
    $assert->fieldExists('addOptions');

    // 5) Verify that the label and machine name are shown in the list.
    $this->drupalGet('/admin/structure/aggrid');
    $this->clickLink('Add ag-Grid Config Entity');
    $aggrid_machine_name = 'test_ag_table';
    $aggrid_label = 'Test AG Table';
    $this->drupalPostForm(
      NULL,
      [
        'label' => $aggrid_label,
        'id' => $aggrid_machine_name,
        'aggridDefault' => '{}',
        'addOptions' => '{}',
      ],
      'Save'
    );
    $this->drupalGet('/admin/structure/aggrid');
    $assert->pageTextContains($aggrid_label);
    $assert->pageTextContains($aggrid_machine_name);

    // Try to re-submit the same ag test, and verify that we see an error
    // message and not a PHP error.
    $this->drupalPostForm(
      Url::fromRoute('entity.aggrid.add_form'),
      [
        'label' => $aggrid_label,
        'id' => $aggrid_machine_name,
        'aggridDefault' => '{}',
        'addOptions' => '{}',
      ],
      'Save'
    );
    $assert->pageTextContains('The machine-readable name is already in use.');

    // 6) Verify that required links are present on respective paths.
    $this->drupalGet(Url::fromRoute('entity.aggrid.collection'));
    $this->assertLinkByHref('/admin/structure/aggrid/add');
    $this->assertLinkByHref('/admin/structure/aggrid/test_ag_table/edit');
    $this->assertLinkByHref('/admin/structure/aggrid/test_ag_table/delete');

    // Verify links on Add ag-Grid.
    $this->drupalGet('/admin/structure/aggrid/add');
    $this->assertLinkByHref('/admin/structure/aggrid');

    // Verify links on Edit ag-Grid.
    $this->drupalGet('/admin/structure/aggrid/test_ag_table/edit');
    $this->assertLinkByHref('/admin/structure/aggrid/test_ag_table/delete');
    $this->assertLinkByHref('/admin/structure/aggrid');

    // Verify links on Delete ag-Grid.
    $this->drupalGet('/admin/structure/aggrid/test_ag_table/delete');
    // List page will be the destination of the cancel link.
    $cancel_button = $this->xpath(
      '//a[@id="edit-cancel" and contains(@href, :path)]',
      [':path' => '/admin/structure/aggrid']
    );
    $this->assertEquals(count($cancel_button), 1, 'Found cancel button linking to list page.');

  }

  /**
   * Wrap an assertion for the action button.
   *
   * @param string $path
   *   Drupal path to a page.
   */
  protected function assertActionButton($path) {
    $button_element = $this->xpath(
      '//a[contains(@class, "button-action") and contains(@data-drupal-link-system-path, :path)]',
      [':path' => $path]
    );
    $this->assertEquals(count($button_element), 1, 'Found action button for path: ' . $path);
  }

}
