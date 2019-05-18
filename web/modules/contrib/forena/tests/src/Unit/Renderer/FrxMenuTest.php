<?php


namespace Drupal\Tests\forena\Unit\Renderer;


class FrxMenuTest extends FrxRendererTestCase {
  // Test report.
  private $doc = '<?xml version="1.0"?>
      <!DOCTYPE root [
      <!ENTITY nbsp "&#160;">
      ]>
      <html xmlns:frx="urn:FrxReports">
      <head>
      <title>Report Title</title>
      <frx:category>Category</frx:category>
      <frx:fields>
      </frx:fields>
      </head>
      <body>
        <div frx:renderer="FrxMenu" menu-id="tools"/>
      </body>
      </html>';

  /**
   * Test FrxXML Renderer
   */
  public function testFrxMenu() {
    $output = $this->render('\Drupal\forena\FrxPlugin\Renderer\FrxMenu', $this->doc);
    $this->assertContains('Menu Item', $output);
  }
}