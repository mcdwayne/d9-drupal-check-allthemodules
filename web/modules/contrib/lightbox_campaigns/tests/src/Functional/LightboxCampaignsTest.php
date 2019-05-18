<?php

namespace Drupal\Tests\lightbox_campaigns\Functional;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\BrowserTestBase;

use Drupal\lightbox_campaigns\Entity\LightboxCampaign;

/**
 * Tests the basic functions of the Lightbox Campaigns module.
 *
 * @todo Add tests of actual lightbox display functionality.
 *
 * @group Lightbox Campaigns
 */
class LightboxCampaignsTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'datetime', 'lightbox_campaigns'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Add the system menu blocks to appropriate regions.
    $this->setupMenus();
  }

  /**
   * Set up menus and tasks in their regions.
   */
  protected function setupMenus() {
    $this->drupalPlaceBlock('system_menu_block:tools', ['region' => 'primary_menu']);
    $this->drupalPlaceBlock('local_tasks_block', ['region' => 'secondary_menu']);
    $this->drupalPlaceBlock('local_actions_block', ['region' => 'content']);
    $this->drupalPlaceBlock('page_title_block', ['region' => 'content']);
  }

  /**
   * Test administrative functions for Lightbox Campaign entities.
   */
  public function testEntityAdministration() {
    $assert = $this->assertSession();

    $web_user = $this->drupalCreateUser([
      'add lightbox campaign',
      'edit lightbox campaign',
      'delete lightbox campaign',
      'administer lightbox campaigns',
    ]);

    // Anonymous user should not have access to campaigns list.
    $this->drupalGet('/admin/content/lightbox_campaigns/list');
    $assert->statusCodeEquals(403);

    $this->drupalLogin($web_user);

    // Web user should have access to campaigns list.
    $this->drupalGet('/admin/content/lightbox_campaigns/list');
    $assert->statusCodeEquals(200);

    // Web user can add entity content.
    $assert->linkExists('Add campaign');
    $this->clickLink(t('Add campaign'));

    $assert->fieldValueEquals('label[0][value]', '');
    $assert->fieldValueEquals('enable[value]', 1);
    $assert->fieldValueEquals('body[0][value]', '');
    $assert->fieldValueEquals('reset', '_none');
    $assert->fieldValueEquals('start[0][value][date]', '');
    $assert->fieldValueEquals('start[0][value][time]', '');
    $assert->fieldValueEquals('end[0][value][date]', '');
    $assert->fieldValueEquals('end[0][value][time]', '');

    // Post content, save an instance. Go back to list after saving.
    $now = new DrupalDateTime('now');
    $next_week = $now->modify('+1 week');
    $edit = [
      'label[0][value]' => 'Test label',
      'enable[value]' => 0,
      'body[0][value]' => 'Test content',
      'reset' => 1800,
      'start[0][value][date]' => $now->format('Y-m-d'),
      'start[0][value][time]' => $now->format('H:i:s'),
      'end[0][value][date]' => $next_week->format('Y-m-d'),
      'end[0][value][time]' => $next_week->format('H:i:s'),
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Entity listed.
    $assert->pageTextContains('Test label');

    // Entity edit page.
    $assert->linkExists('Edit');
    $this->clickLink('Edit');
    $assert->fieldValueEquals('label[0][value]', 'Test label');
    $assert->fieldValueEquals('enable[value]', '');
    $assert->fieldValueEquals('body[0][value]', 'Test content');
    $assert->fieldValueEquals('reset', 1800);
    $assert->fieldValueEquals('start[0][value][date]', $now->format('Y-m-d'));
    $assert->fieldValueEquals('start[0][value][time]', $now->format('H:i:s'));
    $assert->fieldValueEquals(
      'end[0][value][date]',
      $next_week->format('Y-m-d')
    );
    $assert->fieldValueEquals(
      'end[0][value][time]',
      $next_week->format('H:i:s')
    );

    // Delete the entity.
    $assert->linkExists('Delete');
    $this->clickLink('Delete');

    // Confirm deletion.
    $assert->linkExists('Cancel');
    $this->drupalPostForm(NULL, [], 'Delete Campaign');

    // Confirm status message, "%label was deleted".
    $assert->pageTextContains('Test label was deleted');
  }

  /**
   * Test all paths exposed by the module, by permission.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testPaths() {
    $assert = $this->assertSession();

    // Generate an entity so that we can test the paths against it.
    $entity = LightboxCampaign::create(
      [
        'label' => 'Test label',
        'body' => 'Test content',
      ]
    );
    $entity->save();

    // Gather the test data.
    $data = $this->providerTestPaths($entity->id());

    // Run the tests.
    foreach ($data as $datum) {
      // drupalCreateUser() doesn't know what to do with an empty permission
      // array, so we help it out.
      if ($datum[2]) {
        $user = $this->drupalCreateUser([$datum[2]]);
        $this->drupalLogin($user);
      }
      else {
        $user = $this->drupalCreateUser();
        $this->drupalLogin($user);
      }
      $this->drupalGet($datum[1]);
      $assert->statusCodeEquals($datum[0]);
    }
  }

  /**
   * Data provider for testPaths.
   *
   * @param int $entity_id
   *   The id of an existing entity.
   *
   * @return array
   *   Nested array of testing data. Arranged like this:
   *   - Expected response code.
   *   - Path to request.
   *   - Permission for the user.
   */
  protected function providerTestPaths($entity_id) {
    return [
      [
        200,
        '/admin/content/lightbox_campaigns/list',
        'administer lightbox campaigns',
      ],
      [
        403,
        '/admin/content/lightbox_campaigns/list',
        '',
      ],
      [
        200,
        '/admin/content/lightbox_campaigns/add',
        'add lightbox campaign',
      ],
      [
        403,
        '/admin/content/lightbox_campaigns/add',
        '',
      ],
      [
        200,
        '/admin/content/lightbox_campaigns/' . $entity_id . '/edit',
        'edit lightbox campaign',
      ],
      [
        403,
        '/admin/content/lightbox_campaigns/' . $entity_id . '/edit',
        '',
      ],
      [
        200,
        '/admin/content/lightbox_campaigns/' . $entity_id . '/delete',
        'delete lightbox campaign',
      ],
      [
        403,
        '/admin/content/lightbox_campaigns/' . $entity_id . '/delete',
        '',
      ],
    ];
  }

}
