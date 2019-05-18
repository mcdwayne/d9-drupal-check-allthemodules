<?php

namespace Drupal\Tests\chunker\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test for Chunker admin UI.
 *
 * @group Chunker
 */
class ChunkerUITest extends BrowserTestBase {

  /**
   * The entity to use when building test content.
   *
   * @var string
   */
  private $testBundle = 'page';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'field_ui',
    'chunker',
  ];

  /**
   * A user with administration permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A user with permissions to create pages.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    // Bootstraps the site.
    parent::setUp();

    $this->drupalCreateContentType(
      [
        'type' => $this->testBundle,
        'name' => 'Basic page',
      ]
    );

    // Create test user.
    $this->adminUser = $this->drupalCreateUser(
      [
        'access content',
        'administer content types',
        'administer nodes',
        'administer node display',
        'administer node fields',
        'administer node form display',
        'administer filters',
      ],
      'adminUser'
    );
    if (!$this->adminUser) {
      throw new \Exception('Failed to create the admin user to test with. Probably mis-labeled permission.');
    }
    $this->drupalLogin($this->adminUser);

    $this->webUser = $this->drupalCreateUser(
      [
        'create page content',
        'edit own page content',
      ],
      'webUser'
    );

  }

  /**
   * Test that tests run at all.
   */
  public function testImAlive() {
    $this->assert(TRUE);
  }

  /**
   * Add a simple node via the UI.
   */
  public function testCreateContentWithUi() {
    #$this->drupalLogin($this->webUser);

    $edit = [];
    $edit['title[0][value]'] = 'Lots of content';
    $this->drupalPostForm('node/add/' . $this->testBundle, $edit, t('Save'));
    $this->assertText('Lots of content has been created.');
  }


  /**
   * Tweak the page body display options.
   */
  public function testFieldAdminUi() {
    #$this->drupalLogin($this->adminUser);

    $path = 'admin/structure/types/manage/' . $this->testBundle . '/display';
    $this->drupalGet($path);
    // Check our user has the right admin permissions to adjust formatters.
    $this->assertSession()->statusCodeEquals(200);

    // The manage display form is multi part, so pretending to click things.
    // Find and press the cog button.

    // To edit the field formatter settings, can do it by submitting the
    // fields 'cog' button. This is js/no-js safe.
    $edit = [];
    // Take care with these button identifiers. Unsure how volatile they are.
    $this->submitForm($edit, 'body_settings_edit');
    // At this point we should be seeing the field UI edit form with our
    // additional widget options displayed open.
    $this->assertSession()->pageTextContains(t('Chunk text'));
    // And the default current value of it should be '<none>'.
    // Choose to start using chunker here...
    // Retrieving the element is the way to set it. Its ID is ridiculous.
    $select = $this->assertSession()->selectExists(t('Chunk text'));
    // fields[body][settings_edit_form][third_party_settings][chunker][chunker method]
    $select->selectOption('details');
    $this->submitForm($edit, 'body_plugin_settings_update');
    $this->submitForm($edit, 'Save');
    // In testing, I was geting a server error due to chunker missing schema.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains(t('Your settings have been saved.'));

  }

  /**
   * Temp.
   */
  public function testAddingPage() {
    $this->drupalLogin($this->webUser);

    $this->drupalGet('node/add/' . $this->testBundle);

    // Load our sample markup into the body of a node.
    $path = dirname(__FILE__) . '/../../../help/sample_page.html';
    $text = file_get_contents($path);

    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $edit['body[0][value]'] = $text;
    $this->drupalPostForm('node/add/' . $this->testBundle, $edit, t('Save'));
    $this->assertSession()->pageTextContains(t('Basic page @title has been created.', ['@title' => $edit['title[0][value]']]));

    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertTrue($node, 'Node found in database.');

    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains('Greek: hierarchia');
  }

}
