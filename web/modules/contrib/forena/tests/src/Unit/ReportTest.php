<?php
/**
 * @file
 * Implements ReportTest
 */

namespace Drupal\Tests\forena\Unit;

use Drupal\forena\Report;

/**
 * @group Forena
 * @require module forena
 * @coversDefaultClass \Drupal\forena\Report
 */
class ReportTest extends FrxTestCase {

  // Test report.
  private $doc = '<?xml version="1.0"?>
      <!DOCTYPE root [
      <!ENTITY nbsp "&#160;">
      ]>
      <html xmlns:frx="urn:FrxReports">
      <head>
      <title>Report Title</title>
      <frx:category>Category</frx:category>
      <frx:options></frx:options>
      <frx:fields>
        <frx:field id="test_field" link="link" class="class">Default Value</frx:field>
      </frx:fields>
      <frx:commands>
        <frx:ajax command="invoke" method="attr" selector="input#myinput">
          ["checked", "1"]
        </frx:ajax>
      </frx:commands>
      </head>
      <body>
        Report Body
        <div>
          <frx:ajax command="invoke" />
        </div>
        This &amp; That
      </body>
      </html>';

  private $r;
  
  /**
   * Ensure that report object can be created.
   */
  public function testReportParse() {
    // Sample Report To parse.

    $r = new Report($this->doc);

    // Title Check
    $this->assertObjectHasAttribute('title', $r);
    $this->assertEquals("Report Title", $r->title, "Correct Title");

    // Category Check.
    $this->assertObjectHasAttribute('category', $r);
    $this->assertEquals("Category", $r->category);

    // Check to make sure fields are parsed.
    $this->assertArrayHasKey('test_field', $r->replacer->fields);

    $this->assertEquals(1, count($r->commands), 'Commands parsed');
  }

  /**
   * Simple Render Test
   */
  public function testSimpleRender() {
    $this->documentManager()->setDocument('drupal');
    $this->documentManager()->getDocument()->clear();
    $r = new Report($this->doc);
    $r->render('drupal', FALSE);
    $content = $this->getDocument()->flush();
    // Make sure we have a report.
    $this->assertArrayHasKey('report', $content);
    $this->assertContains('Report Body',$content['report']['#template']);
    // Make sure we don't have a parameters form.
    $this->assertArrayNotHasKey('parameters', $content);
    // Make sure that we don't have the the ajax command
    $this->assertNotContains('ajax', $content['report']['#template']);
    //echo  $content['report']['#template'];
    $this->assertContains('This &amp; That', $content['report']['#template']);
  }

  /**
   * Test a simple report.
   */
  public function testSimpleReport() {
    $this->initParametersForm(); 
    $content = $this->report('sample', ['specified_parameter' => 'specified']);
    $this->assertArrayHasKey('report', $content);
    $this->assertContains('<table>',$content['report']['#template']);

    // Make sure we have a title
    $this->assertArrayHasKey('#title', $content);
    $this->assertEquals('Sample Report', $content['#title'], 'Content has title');

    // Check to make sure our default parameter was set
    $parms = $this->getDataContext('parm');
    $this->assertArrayHasKey('default_parameter', $parms);
    $this->assertEquals('test', $parms['default_parameter']);

    // Make sure passed parameters survive.
    $this->assertArrayHasKey('specified_parameter', $parms);
    $this->assertEquals('specified', $parms['specified_parameter']);

    // Check to make sure we have content.
    $this->assertArrayHasKey('report', $content);
    $this->assertArrayHasKey('#template', $content['report']);
    $this->assertContains('<table>',$content['report']['#template']);
    $this->assertContains('this &amp; that', $content['report']['#template']);

    // Verify that a parameters form has been build
    $this->assertArrayHasKey('parameters', $content);
    $form = $content['parameters'];
    $this->assertArrayHasKey('parms', $form);
    $this->assertArrayHasKey('default_parameter', $form['parms']);

    // Verify that the css js library has been loaded
    $this->assertArrayHasKey('#attached', $content);
    $this->assertArrayHasKey('library', $content['#attached']);
    $library = $content['#attached']['library'];
    $this->assertContains('forena/skin.default', $library, "Skin Library Loaded");
    $this->assertContains('core/drupal.ajax', $library, "Core library added");
    $this->assertContains('core/drupal.dialog.ajax', $library,
      "Report Specific Library added"); 
  }

  public function testParameterTypes() {
    $content = $this->report('parameter_test');
    // Verfiy that a parameters form has been build
    $this->assertArrayHasKey('parameters', $content);
    $form = $content['parameters'];
    $this->assertArrayHasKey('parms', $form);

    //@TODO: Extend testing to cover all parameter control types.
    $parms = $form['parms'];
    $this->assertArrayHasKey('textfield', $parms);
    $this->assertArrayHasKey('select', $parms);
  }

  public function testIncludedReport() {
    $content = $this->report('include');
    $report = $content['report']['#template'];
    $this->assertContains('Header', $report, 'Header in tact');
    $this->assertContains('col1', $report, 'Embedded report.');
    $this->assertContains('Footer', $report, 'Footer in tact');
    $this->assertContains('<a href', $report, 'Link got generated'); 
  }

}