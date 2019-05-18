<?php

namespace Drupal\Tests\og_sm_path\Kernel;

use Drupal\og_sm\OgSm;
use Drupal\og_sm_path\OgSmPath;
use Drupal\Tests\og_sm\Kernel\OgSmKernelTestBase;
use Drupal\token\Tests\TokenTestTrait;

/**
 * Tests Site Path tokens.
 *
 * @group og_sm
 */
class PathTokensTest extends OgSmKernelTestBase {

  use TokenTestTrait;

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
  private $nodeSite;

  /**
   * The Site node path to test with.
   *
   * @var string
   */
  private $nodePath;

  /**
   * Site content node to test with.
   *
   * @var \Drupal\node\NodeInterface
   */
  private $nodeSiteContent;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create the Site node with his path.
    $type = $this->createGroupNodeType(OgSmKernelTestBase::TYPE_IS_GROUP);
    OgSm::siteTypeManager()->setIsSiteType($type, TRUE);
    $type->save();

    $this->nodeSite = $this->createGroup($type->id());
    $this->nodePath = '/test-path-token';
    OgSmPath::sitePathManager()->setPath($this->nodeSite, $this->nodePath);

    // Create the Site content node.
    $type_content = $this->createGroupContentNodeType(OgSmKernelTestBase::TYPE_IS_GROUP_CONTENT);
    $sites = [$this->nodeSite];
    $this->nodeSiteContent = $this->createGroupContent($type_content->id(), $sites);
  }

  /**
   * Test the token generation.
   */
  public function testSiteTokens() {
    $type = 'node';
    $token = 'site-path';

    // No tokens for non Site or its content.
    $node = $this->createNode();
    $data = ['node' => $node];
    $this->assertToken($type, $data, $token, NULL);

    // Token if node is Site.
    $data = ['node' => $this->nodeSite];
    $this->assertToken($type, $data, $token, $this->nodePath);

    // Token if node is Site content.
    $data = ['node' => $this->nodeSiteContent];
    $this->assertToken($type, $data, $token, $this->nodePath);
  }

}
