<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 3/23/2016
 * Time: 9:58 AM
 */

namespace Drupal\Tests\forena\Unit;


use Drupal\forena\ReportManager;

/**
 * Test Report Manager
 * @group Forena
 * @require module forena
 * @coversDefaultClass \Drupal\forena\ReportManager
 */
class ReportManagerTest extends FrxTestCase {

  /**
   * Test extraction from url.
   */
  public function testReportName() {
    $r = ReportManager::instance();
    // Normal reprot name
    $report_name = 'test';
    $ext = $r->formatFromPath($report_name);
    $this->assertEquals('test', $report_name);
    $this->assertEquals('drupal', $ext);

    // CSV supported format
    $report_name = 'test.csv';
    $ext = $r->formatFromPath($report_name);
    $this->assertEquals('test', $report_name);
    $this->assertEquals('csv', $ext);

    // Direcotry name as a period
    $report_name = 'test.unsupported';
    $ext = $r->formatFromPath($report_name);
    $this->assertEquals('test.unsupported', $report_name);
    $this->assertEquals('drupal', $ext);
  }

  public function testReportInclude() {
    $doc = $this->getDocument();
    $doc->clear();
    ReportManager::instance()->reportInclude('sample');
    $content = $doc->flush();
    $output = $content['report']['#template'];
    $this->assertContains('col1', $output);

  }
}