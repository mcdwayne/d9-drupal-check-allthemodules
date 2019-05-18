<?php
/**
 * @file
 * Implements ReportFileSystemTest
 */

namespace Drupal\Tests\forena\Unit\File;

use Drupal\Tests\forena\Unit\Mock\TestingDataManager;
use Drupal\Tests\forena\Unit\Mock\TestingReportFileSystem;
use Drupal\Tests\forena\Unit\FrxTestCase;

/**
 * @require module forena
 * @group Forena
 * @coversDefaultClass \Drupal\forena\File\ReportFileSystem
 */
class ReportFileSystemTest extends FrxTestCase {


  /**
   * Test File Processing
   */
  public function testFileExists() {
    $this->assertTrue($this->reportFileSystem()->exists('sample.frx'), "Sample Report");
    return 'File operations OK';
  }

  /**
   * Determine if the object scanning work.
   */
  public function testDirectoryScan() {

    $this->reportFileSystem()->scan();
    // Retrieve cache entry
    $o = $this->reportFileSystem()->getMetaData('sample.frx');
    $this->assertContains('sample.frx', $o->file);

    // Check for metadata
    $this->assertObjectHasAttribute('metaData', $o);

    // Retrieve the README from the reports directory
    $o = $this->reportFileSystem()->getMetaData('README.txt');
    $this->assertNotNull($o);
    $this->assertContains('README', $o->file);
  }


  /**
   * User Reports by category.
   */
  public function testUserReports() {
    $reports = $this->reportFileSystem()->reportsByCategory();
    $this->assertArrayHasKey('Test', $reports);
    $this->assertArrayHasKey('Sample', $reports);
  }
}