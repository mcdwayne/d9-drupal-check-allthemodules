<?php

namespace Drupal\Tests\og_sm\Kernel;

use Drupal\og_sm\OgSm;

/**
 * Tests about the node type settings.
 *
 * @group og_sm
 */
class NodeTypeTest extends OgSmKernelTestBase {

  /**
   * Test Site type helpers.
   */
  public function testSiteType() {
    $type_is_group = $this->createGroupNodeType(self::TYPE_IS_GROUP);

    $site_type_manager = OgSm::siteTypeManager();

    // Default no site types.
    $this->assertEquals([], $site_type_manager->getSiteTypes());
    $this->assertFalse($site_type_manager->isSiteType($type_is_group));

    // Add the type.
    $site_type_manager->setIsSiteType($type_is_group, TRUE);
    $type_is_group->save();
    $this->assertTrue($site_type_manager->isSiteType($type_is_group));
    $this->assertEquals([self::TYPE_IS_GROUP => $type_is_group], $site_type_manager->getSiteTypes());

    // Remove the type.
    $site_type_manager->setIsSiteType($type_is_group, FALSE);
    $type_is_group->save();
    $this->assertFalse($site_type_manager->isSiteType($type_is_group));
    $this->assertEquals([], $site_type_manager->getSiteTypes());
  }

}
