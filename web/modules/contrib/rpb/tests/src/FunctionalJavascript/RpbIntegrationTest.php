<?php

namespace Drupal\Tests\rpb\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;

/**
 * Tests the JavaScript functionality of the Views REST preview.
 *
 * @group Rpb
 */
class RpbIntegrationTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rpb', 'rpb_test'];

  private $admin_user;

  private $views_xml_link;

  private $views_json_link;

  private $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->admin_user = $this->drupalCreateUser([
      'administer views',
    ]);
    $this->drupalLogin($this->admin_user);

    $this->views_xml_link = 'admin/structure/views/view/rpb_test/edit/rest_export_1';
    $this->views_json_link = 'admin/structure/views/view/rpb_test/edit/rest_export_2';
    // Create node object.
    $this->node = Node::create([
      'type' => 'page',
      'title' => 'My dummy page'
    ]);
    $this->node->save();
  }

  /**
   * Tests if xml output is highlited correctly.
   */
  public function testViewsXmlPreview() {
    $this->drupalGet($this->views_xml_link);

    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', '.hljs.xml'));
    $this->assertSession()->assertWaitOnAjaxRequest();
    
    $page = $this->getSession()->getPage();
    $content = $page->find('css', 'span.php')->getHtml();

    $this->assertSession()->assert(preg_match('/hljs-meta/', $content), 'hljs classes found.');
  }

  /**
   * Tests if json output is highlited correctly.
   */
  public function testViewsJsonPreview() {
    $this->drupalGet($this->views_json_link);

    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', '.hljs.json'));
    $this->assertSession()->assertWaitOnAjaxRequest();
    
    $page = $this->getSession()->getPage();
    $content = $page->find('css', '.hljs.json')->getHtml();

    $this->assertSession()->assert(preg_match('/hljs-attr/', $content), 'hljs classes found.');
  }

}
