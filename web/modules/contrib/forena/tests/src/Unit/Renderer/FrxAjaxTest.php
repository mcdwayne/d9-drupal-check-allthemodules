<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/21/16
 * Time: 6:58 PM
 */

namespace Drupal\Tests\forena\Unit\Renderer;

/**
 * @group Forena
 * @require module forena
 * @coversDefaultClass \Drupal\forena\FrxPlugin\Renderer\FrxAjax
 */
class FrxAjaxTest extends FrxRendererTestCase {
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
        <div frx:renderer="FrxAjax" frx:selector="input" frx:command="invoke" frx:method="attr">[ "checked" , "checked" ]</div>
      </body>
      </html>';
  /**
   * Test for new ajax renderer.
   */
  public function testAjaxRender() {
    $this->getDocument()->clear(); 
    $output = $this->render('\Drupal\forena\FrxPlugin\Renderer\FrxAjax', $this->doc);
    $this->assertEmpty($output, "Ajax Doesn't produce output");
    $commands = $this->getDocument()->getAjaxCommands();
    $this->assertEquals(1, count($commands['post']));
  }
}