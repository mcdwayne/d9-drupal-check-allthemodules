<?php
/**
 * @file
 * Contains Drupal\Tests\block_render\Unit\Utility\LibraryUtilityTest.
 */

namespace Drupal\Tests\block_render\Unit\Utility;

use Drupal\block_render\Utility\LibraryUtility;
use Drupal\Tests\UnitTestCase;

/**
 * Tests utility to retrieve necessary libraries.
 *
 * @group block_render
 */
class LibraryUtilityTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function testGetLibraryResponse() {
    $library_discovery = $this->getMockBuilder('Drupal\Core\Asset\LibraryDiscoveryInterface')
      ->getMock();
    $library_discovery->expects($this->once())
      ->method('getLibraryByName')
      ->with('vendor', 'library')
      ->will($this->returnValue([
          'name' => 'vendor/library',
          'version' => '1.0.0',
        ]));

    $library_dependency_resolver = $this->getMockBuilder('Drupal\Core\Asset\LibraryDependencyResolverInterface')
      ->getMock();
    $library_dependency_resolver->expects($this->exactly(2))
      ->method('getLibrariesWithDependencies')
      ->will($this->onConsecutiveCalls(['vendor/library'], ['vendor/loadedlibrary']));

    $library_utlity = new LibraryUtility($library_discovery, $library_dependency_resolver);

    $assets = $this->getMockBuilder('Drupal\Core\Asset\AttachedAssetsInterface')
      ->getMock();
    $assets->expects($this->once())
      ->method('getLibraries')
      ->will($this->returnValue([
          'vendor/library',
        ]));
    $assets->expects($this->once())
      ->method('getAlreadyLoadedLibraries')
      ->will($this->returnValue([
          'vendor/loadedlibrary',
        ]));

    $response = $library_utlity->getLibraryResponse($assets);

    $this->assertInstanceOf('Drupal\block_render\Libraries\LibrariesInterface', $response);
    $libraries = $response->getLibraries();

    $this->assertInternalType('array', $libraries);
    $this->assertArrayHasKey(0, $libraries);
    $this->assertInstanceOf('Drupal\block_render\Library\LibraryInterface', $libraries[0]);
    $this->assertEquals('library', $libraries[0]->getName());
    $this->assertEquals('1.0.0', $libraries[0]->getVersion());
  }

}
