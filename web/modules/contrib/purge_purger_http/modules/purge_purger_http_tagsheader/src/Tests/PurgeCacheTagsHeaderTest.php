<?php

namespace Drupal\purge_purger_http_tagsheader\Tests;

use Symfony\Component\HttpFoundation\Request;
use Drupal\purge\Tests\KernelTestBase;

/**
 * Tests \Drupal\purge_purger_http_tagsheader\Plugin\Purge\TagsHeader\PurgeCacheTagsHeader.
 *
 * @group purge_purger_http_tagsheader
 */
class PurgeCacheTagsHeaderTest extends KernelTestBase {
  public static $modules = ['system', 'purge_purger_http_tagsheader'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('system', ['router']);
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Test that the header value is exactly as expected (space separated).
   */
  public function testHeaderValue() {
    $request = Request::create('/system/401');
    $response = $this->container->get('http_kernel')->handle($request);
    $tags_header = $response->headers->get('Purge-Cache-Tags');
    $tags = explode(' ', $tags_header);
    $this->assertEqual(200, $response->getStatusCode());
    $this->assertTrue(is_string($tags_header));
    $this->assertTrue(strlen($tags_header));
    $this->assertTrue(in_array('config:user.role.anonymous', $tags));
    $this->assertTrue(in_array('http_response', $tags));
    $this->assertTrue(in_array('rendered', $tags));
  }

}
