<?php

namespace Drupal\Tests\og_sm_path\Kernel;

use Drupal\og_sm\OgSm;
use Drupal\og_sm_path\OgSmPath;
use Drupal\Tests\og_sm\Kernel\OgSmKernelTestBase;

/**
 * Tests Site Path functionality.
 *
 * @group og_sm
 */
class SitePathTest extends OgSmKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'og_sm_config',
    'ctools',
    'path',
    'pathauto',
    'token',
    'og_sm_path',
  ];

  /**
   * The Site node to test with.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $nodeSite;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $type = $this->createGroupNodeType(OgSmKernelTestBase::TYPE_IS_GROUP);
    OgSm::siteTypeManager()->setIsSiteType($type, TRUE);
    $type->save();
    $this->nodeSite = $this->createGroup($type->id());
  }

  /**
   * Test the Path API.
   */
  public function testPath() {
    $site_path_manager = OgSmPath::sitePathManager();

    // Test to get the path for a Site.
    $path = '/test-path';
    $site_path_manager->setPath($this->nodeSite, $path);
    $this->assertEquals($path, $site_path_manager->getPathFromSite($this->nodeSite));

    // Test to get a Site for non existing path.
    $this->assertFalse($site_path_manager->getSiteFromPath('/foo-bar-fizz-buzz'));

    // Test to get a Site for an existing path.
    $this->assertEquals($this->nodeSite->id(), $site_path_manager->getSiteFromPath($path)->id());
  }

}
