<?php
/**
 * @file
 * Contains Drupal\Tests\block_render\Unit\Response\AssetResponseTest.
 */

namespace Drupal\Tests\block_render\Unit\Response;

use Drupal\block_render\Response\AssetResponse;
use Drupal\Tests\UnitTestCase;

/**
 * The asset response data test.
 *
 * @group block_render
 */
class AssetResponseTest extends UnitTestCase {

  /**
   * Libraries.
   *
   * @var Drupal\block_render\Data\LibraryResponseInterface
   */
  protected $libraries;

  /**
   * Footer Assets.
   *
   * @var array
   */
  protected $response;

  /**
   * Setup the tests.
   */
  public function setUp() {
    $this->libraries = $this->getMockBuilder('Drupal\block_render\Libraries\LibrariesInterface')
      ->getMock();

    $header = [
      'test_header' => 'Header',
    ];

    $footer = [
      'test_footer' => 'Footer',
    ];

    $this->response = new AssetResponse($this->libraries, $header, $footer);
  }

  /**
   * Tests getting libraries.
   */
  public function testGetLibraries() {
    $this->assertEquals($this->libraries, $this->response->getLibraries());
  }

  /**
   * Tests getting the header.
   */
  public function testGetHeader() {
    $header = $this->response->getHeader();
    $this->assertInternalType('array', $header);
    $this->assertArrayHasKey('test_header', $header);
    $this->assertInternalType('string', $header['test_header']);
    $this->assertEquals('Header', $header['test_header']);
  }

  /**
   * Tests getting the footer.
   */
  public function testGetFooter() {
    $footer = $this->response->getFooter();
    $this->assertInternalType('array', $footer);
    $this->assertArrayHasKey('test_footer', $footer);
    $this->assertInternalType('string', $footer['test_footer']);
    $this->assertEquals('Footer', $footer['test_footer']);
  }

  /**
   * Tests setting a property directly.
   */
  public function testSetFailure() {
    $this->setExpectedException('LogicException', 'You cannot set properties.');
    $this->response->test = '';
  }

}
