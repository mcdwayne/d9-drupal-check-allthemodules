<?php

namespace Drupal\Tests\og_sm_content\Functional;

use Drupal\og_sm\OgSm;
use Drupal\Tests\og_sm\Functional\OgSmWebTestBase;

/**
 * Tests administering content within a site context.
 *
 * @group og_sm
 */
class SiteContentTest extends OgSmWebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'og_sm_content',
    'og_sm_context',
  ];

  /**
   * The admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userAdministrator;

  /**
   * Site manager.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userSiteManager;

  /**
   * Site editor.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userSiteEditor;

  /**
   * The site node type.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $siteType;

  /**
   * The site content node type.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $siteContentType;

  /**
   * Site 1.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $site1;

  /**
   * Site 2.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $site2;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create Sites.
    $this->siteType = $this->createGroupNodeType(OgSmWebTestBase::TYPE_IS_GROUP);
    OgSm::siteTypeManager()->setIsSiteType($this->siteType, TRUE);
    $this->siteType->save();
    $this->site1 = $this->createGroup($this->siteType->id());
    $this->site2 = $this->createGroup($this->siteType->id());

    // Create site content type.
    $this->siteContentType = $this->createGroupContentNodeType(OgSmWebTestBase::TYPE_IS_GROUP_CONTENT);

    // Create users.
    $this->userAdministrator = $this->drupalCreateUser([], NULL, TRUE);
    $this->userSiteManager = $this->createGroupUser([], [$this->site1], [
      'access content overview',
      'create ' . OgSmWebTestBase::TYPE_IS_GROUP_CONTENT . ' content',
      'edit any ' . OgSmWebTestBase::TYPE_IS_GROUP_CONTENT . ' content',
      'delete any ' . OgSmWebTestBase::TYPE_IS_GROUP_CONTENT . ' content',
      'administer site',
    ]);
    $this->userSiteEditor = $this->createGroupUser([], [$this->site1], [
      'access my content overview',
      'create ' . OgSmWebTestBase::TYPE_IS_GROUP_CONTENT . ' content',
      'edit own ' . OgSmWebTestBase::TYPE_IS_GROUP_CONTENT . ' content',
      'delete own ' . OgSmWebTestBase::TYPE_IS_GROUP_CONTENT . ' content',
    ]);

    $this->config('og.settings')
      ->set('group_resolvers', ['og_sm_context_node', 'og_sm_context_group_path'])
      ->save();
  }

  /**
   * Tests administering site content with views disabled and enabled.
   */
  public function testContentOverview() {
    // Create content across different sites as admin.
    $this->createGroupContent($this->siteContentType->id(), [$this->site1], ['uid' => $this->userAdministrator->id()]);
    $this->createGroupContent($this->siteContentType->id(), [$this->site2], ['uid' => $this->userAdministrator->id()]);

    // Create content on site 1 as site editor.
    $this->createGroupContent($this->siteContentType->id(), [$this->site1], ['uid' => $this->userSiteEditor->id()]);

    // Verify that the content overview is filtered by site context.
    $this->verifyContentOverviewPages();
    // Enable views.
    \Drupal::service('module_installer')->install(['views']);
    // Verify that the content overview is now a view.
    $this->drupalLogin($this->userAdministrator);
    $this->drupalGet('group/node/' . $this->site1->id() . '/admin/content');
    $this->assertTrue((bool) $this->cssSelect('.view.view-og-sm-site-content'), 'Site content view has been found');

    // Verify that the context filtering remains after the overviews have been
    // replaced by views.
    $this->verifyContentOverviewPages();
  }

  /**
   * Tests administering site content.
   *
   * This is a separate method because we want to check that this remains the
   * same when views is enabled.
   *
   * @see testContentOverview()
   */
  protected function verifyContentOverviewPages() {
    $site1_path = 'group/node/' . $this->site1->id();
    $site2_path = 'group/node/' . $this->site2->id();

    // Login as admin.
    $this->drupalLogin($this->userAdministrator);
    // Visit site 1 content overview.
    $this->drupalGet($site1_path . '/admin/content');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCount(2, $this->xpath('//table/tbody/tr'));
    // Visit site 2 content overview.
    $this->drupalGet($site2_path . '/admin/content');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCount(1, $this->xpath('//table/tbody/tr'));

    // Login as site manager.
    $this->drupalLogin($this->userSiteManager);
    // Visit site 1 content overview.
    $this->drupalGet($site1_path . '/admin/content');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCount(2, $this->xpath('//table/tbody/tr'));
    // Visit site 2 content overview.
    $this->drupalGet($site2_path . '/admin/content');
    $this->assertSession()->statusCodeEquals(403);

    // Login as site editor.
    $this->drupalLogin($this->userSiteEditor);
    // Visit site 1 content overview.
    $this->drupalGet($site1_path . '/admin/content');
    $this->assertSession()->statusCodeEquals(403);
    // Visit site 1 my content overview.
    $this->drupalGet($site1_path . '/admin/content/my');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCount(1, $this->xpath('//table/tbody/tr'));
  }

  /**
   * Tests the add content page.
   */
  public function testAddContentPage() {
    $site1_path = 'group/node/' . $this->site1->id();
    $site2_path = 'group/node/' . $this->site2->id();

    // Login as admin.
    $this->drupalLogin($this->userAdministrator);
    // Verify that we get redirected to the node add form if there is only 1
    // available content type.
    $this->drupalGet($site1_path . '/content/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals($site1_path . '/content/add/' . $this->siteContentType->id());

    // Create a second site content type.
    $this->createGroupContentNodeType('page');
    // Verify that we remain on the content/add page now.
    $this->drupalGet($site1_path . '/content/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals($site1_path . '/content/add');
    // Verify that the node/add page has a link for both content types to create
    // content within a site context.
    $this->assertSession()->linkByHrefExists($site1_path . '/content/add/' . $this->siteContentType->id());
    $this->assertSession()->linkByHrefExists($site1_path . '/content/add/page');

    // Login as the site editor.
    $this->drupalLogin($this->userSiteEditor);
    // Verify that the editor is still redirected when visiting the content/add
    // page since he doesn't have permission to create the new content type.
    $this->drupalGet($site1_path . '/content/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals($site1_path . '/content/add/' . $this->siteContentType->id());
    // Verify that the editor cannot create any content on site 2.
    $this->drupalGet($site2_path . '/content/add');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($site2_path . '/content/add/' . $this->siteContentType->id());
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests adding content within a site context.
   */
  public function testAddContentForm() {
    $site1_path = 'group/node/' . $this->site1->id();
    $site2_path = 'group/node/' . $this->site2->id();
    $site_manager = OgSm::siteManager();

    // Login as admin.
    $this->drupalLogin($this->userAdministrator);
    // Create a node as admin on site 2.
    $this->drupalPostForm($site2_path . '/content/add', [
      'title[0][value]' => 'test site 2 content by admin',
    ], 'Save');
    $site2_node = $this->getNodeByTitle('test site 2 content by admin');
    // Verify that the newly created node is linked to site 2.
    $this->assertEquals($this->site2->id(), $site_manager->getSiteFromEntity($site2_node)->id());

    $this->drupalPostForm($site1_path . '/content/add', [
      'title[0][value]' => 'test site 1 content by admin',
    ], 'Save');
    $site1_node = $this->getNodeByTitle('test site 1 content by admin');

    // Login as site manager.
    $this->drupalLogin($this->userSiteManager);
    // Verify that the site manager cannot access the newly created node since
    // it belongs to site 2.
    $this->drupalGet('node/' . $site2_node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    // He can edit the node created by admin for site 1.
    $this->drupalGet('node/' . $site1_node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    // Verify that the site manager can access the promote checkbox, event
    // though he does not have the global "administer nodes" permission.
    $this->assertSession()->fieldExists('promote[value]');

    $this->drupalPostForm($site1_path . '/content/add', [
      'title[0][value]' => 'test site 1 content by site manager',
    ], 'Save');
    $site1_node2 = $this->getNodeByTitle('test site 1 content by site manager');

    // Login as site editor.
    $this->drupalLogin($this->userSiteEditor);
    // Verify that site editor cannot edit any of the created nodes for site 1
    // since he can only edit his own content.
    $this->drupalGet('node/' . $site1_node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('node/' . $site1_node2->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalPostForm($site1_path . '/content/add', [
      'title[0][value]' => 'test site 1 content by site editor',
    ], 'Save');
    $site1_node3 = $this->getNodeByTitle('test site 1 content by site editor');
    // Verify that the site editor can access his own content.
    $this->drupalGet('node/' . $site1_node3->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    // Verify that the site manager cannot access the promote checkbox.
    $this->assertSession()->fieldNotExists('promote[value]');
  }

}
