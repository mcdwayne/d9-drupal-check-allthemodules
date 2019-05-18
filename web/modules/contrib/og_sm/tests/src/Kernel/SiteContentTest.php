<?php

namespace Drupal\Tests\og_sm\Kernel;

use Drupal\og_sm\OgSm;

/**
 * Tests about the node type settings.
 *
 * @group og_sm
 */
class SiteContentTest extends OgSmKernelTestBase {

  /**
   * Test Site Content helpers.
   */
  public function testSiteContent() {
    $type_group = $this->createGroupNodeType(self::TYPE_IS_GROUP);
    $type_group_no_site = $this->createGroupContentNodeType('not_a_site_type');
    $type_group_content = $this->createGroupContentNodeType(self::TYPE_IS_GROUP_CONTENT);

    $group1 = $this->createGroup($type_group->id());
    $group2 = $this->createGroup($type_group->id());
    $group3 = $this->createGroup($type_group_no_site->id());
    $groups = [$group3, $group2, $group1];

    // Test getting the groups from site content.
    $content_no_groups = $this->createGroupContent($type_group_content->id());
    $content_with_groups = $this->createGroupContent($type_group_content->id(), $groups);

    $site_manager = OgSm::siteManager();

    // Create group content.
    $this->assertEquals([], $site_manager->getSitesFromEntity($content_no_groups));
    $this->assertEquals([], $site_manager->getSitesFromEntity($content_with_groups));
    $this->assertFalse($site_manager->getSiteFromEntity($content_with_groups));
    $this->assertFalse($site_manager->isSiteContent($content_with_groups));
    $this->assertFalse($site_manager->contentBelongsToSite($content_with_groups, $group1));

    // Make the group type a Site type.
    OgSm::siteTypeManager()->setIsSiteType($type_group, TRUE);
    $type_group->save();

    // Get all sites a node belongs to.
    $sites = $site_manager->getSitesFromEntity($content_with_groups);
    $this->assertCount(2, $sites);

    // Get a site (first membership).
    $site = $site_manager->getSiteFromEntity($content_with_groups);
    $this->assertEquals($group1->id(), $site->id());

    // Content should be Site content.
    $this->assertTrue($site_manager->isSiteContent($content_with_groups));

    // Content should be a member of both groups.
    $this->assertTrue($site_manager->contentBelongsToSite($content_with_groups, $group1));
    $this->assertTrue($site_manager->contentBelongsToSite($content_with_groups, $group2));

    // No Site group types are ignored.
    $this->assertFalse($site_manager->contentBelongsToSite($content_with_groups, $group3));
  }

  /**
   * Test Site content type helpers.
   */
  public function testContentGetTypes() {
    $this->createGroupNodeType(self::TYPE_IS_GROUP);
    $this->createNodeType(self::TYPE_DEFAULT);

    $site_content_zzz = $this->createGroupContentNodeType('og_sm_type_zzz');
    $site_content_aaa = $this->createGroupContentNodeType('og_sm_type_aaa');

    $expected = [
      $site_content_aaa->label() => $site_content_aaa,
      $site_content_zzz->label() => $site_content_zzz,
    ];
    $this->assertEquals($expected, OgSm::siteTypeManager()->getContentTypes());
  }

  /**
   * Test Content type is Site content type.
   */
  public function testIsSiteContentType() {
    $not_site_content_type = $this->createNodeType(self::TYPE_DEFAULT);
    $is_site_content_type = $this->createGroupContentNodeType(self::TYPE_IS_GROUP_CONTENT);
    $site_type_manager = OgSm::siteTypeManager();

    $this->assertFalse(
      $site_type_manager->isSiteContentType($not_site_content_type),
      'Global content type without OG Audience field is not a Site content type.'
    );
    $this->assertTrue(
      $site_type_manager->isSiteContentType($is_site_content_type),
      'Global content type with OG Audience field id Site content type.'
    );
  }

}
