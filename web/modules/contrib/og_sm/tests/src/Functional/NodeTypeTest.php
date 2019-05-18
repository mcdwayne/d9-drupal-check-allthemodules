<?php

namespace Drupal\Tests\og_sm\Functional;

use Drupal\node\Entity\NodeType;
use Drupal\og_sm\OgSm;

/**
 * Tests about the node type forms.
 *
 * @group og_sm
 */
class NodeTypeTest extends OgSmWebTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $user = $this->drupalCreateUser(['administer content types']);
    $this->drupalLogin($user);
  }

  /**
   * Test config form.
   */
  public function testNodeTypeForm() {
    $type_not_group = $this->createNodeType(self::TYPE_DEFAULT);
    $type_is_group = $this->createGroupNodeType(self::TYPE_IS_GROUP);

    $url_not_group = 'admin/structure/types/manage/' . $type_not_group->id();
    $url_is_group = 'admin/structure/types/manage/' . $type_is_group->id();

    // Form elements.
    $submit = t('Save content type');

    // Default no nodes as site.
    $site_type_manager = OgSm::siteTypeManager();
    $this->assertEquals([], $site_type_manager->getSiteTypes());

    // Check if the Site Manager field is in the form.
    $this->drupalGet($url_not_group);
    $this->assertSession()->fieldExists('edit-og-sm-site-type');

    // Post the form with Site settings enabled for a non Group, this should
    // result in an error on screen.
    $this->drupalPostForm($url_not_group, ['og_sm_site_type' => TRUE], $submit);
    $this->assertSession()->pageTextContains(t('A content type can only be a Site if it also a Group type.'));
    $this->assertEquals([], $site_type_manager->getSiteTypes());

    // Post it for a Group node should be successful.
    $this->drupalPostForm($url_is_group, ['og_sm_site_type' => TRUE], $submit);
    $this->assertSession()->responseContains(t('The content type %type has been updated.', ['%type' => $type_is_group->label()]));
    $type_is_group = NodeType::load($type_is_group->id());
    $this->assertEquals([$type_is_group->id() => $type_is_group], $site_type_manager->getSiteTypes());

    // Check if the checkbox is active.
    $this->drupalGet($url_is_group);
    $this->assertSession()->fieldExists('edit-og-sm-site-type');

    // Remove a node type from the Site types.
    $this->drupalPostForm($url_is_group, ['og_sm_site_type' => FALSE], $submit);
    $this->assertSession()->responseContains(t('The content type %type has been updated.', ['%type' => $type_is_group->label()]));
    $this->assertEquals([], $site_type_manager->getSiteTypes());

    // Check if the checkbox is no longer active.
    $this->drupalGet($url_is_group);
    $this->assertSession()->fieldExists('edit-og-sm-site-type');
  }

  /**
   * Test deleting a site type.
   */
  public function testDeleteSiteNodeType() {
    $type_is_group = $this->createGroupNodeType(self::TYPE_IS_GROUP);
    $site_type_manager = OgSm::siteTypeManager();

    $site_type_manager->setIsSiteType($type_is_group, TRUE);
    $type_is_group->save();
    $this->assertEquals([$type_is_group->id() => $type_is_group], $site_type_manager->getSiteTypes());

    $url_delete = 'admin/structure/types/manage/' . $type_is_group->id() . '/delete';
    $this->drupalPostForm($url_delete, [], t('Delete'));
    $this->assertEquals([], $site_type_manager->getSiteTypes());
  }

}
