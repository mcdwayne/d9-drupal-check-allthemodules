<?php

namespace Drupal\Tests\widget_engine_entity_form\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Widget engine entity form Javascript functional tests.
 *
 * @group widget_engine_entity_form
 */
class WidgetEngineEntityBrowserEntityFormTest extends JavascriptTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'widget_engine_entity_form_test',
    'ctools',
    'views',
    'block',
    'node',
    'file',
    'image',
    'field_ui',
    'views_ui',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $account = $this->drupalCreateUser([
      'access test_browser_for_widgets entity browser pages',
      'create article content',
      'access content',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Test for select_add_tabs entity browser widget selector.
   */
  public function testEntityForm() {
    // Create new widget
    $this->drupalGet('node/add/article');
    $this->getSession()->getPage()->clickLink('Add a new widget');
    $this->getSession()->switchToIFrame('entity_browser_iframe_test_browser_for_widgets');
    $this->getSession()->getPage()->fillField('inline_entity_form[name][0][value]', 'Banana');
    $this->getSession()->getPage()->pressButton('Save entity');
    $this->getSession()->switchToIFrame();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Banana');
    $this->getSession()->getPage()->fillField('title[0][value]', 'Monkey');
    $this->getSession()->getPage()->pressButton('Save');
    $parent_node = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->loadByProperties(['title' => 'Monkey']);
    $parent_node = current($parent_node);
    $this->assertEquals('Banana', $parent_node->field_widget_reference->entity->label(), 'Child node has correct title.');

    // Select exist widget.
    $this->drupalGet('node/add/article');
    $this->getSession()->getPage()->clickLink('Select widgets');
    $this->getSession()->switchToIFrame('entity_browser_iframe_test_browser_for_widgets');
    $this->getSession()->getPage()->fillField('name', 'Banana');
    $this->getSession()->getPage()->pressButton('Apply');
    $this->getSession()->getPage()->checkField('entity_browser_select[widget:1]');
    $this->getSession()->getPage()->pressButton('Select entities');
    $this->getSession()->switchToIFrame();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Banana');
    $this->getSession()->getPage()->fillField('title[0][value]', 'MegaMonkey');
    $this->getSession()->getPage()->pressButton('Save');

    $parent_node = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->loadByProperties(['title' => 'MegaMonkey']);
    $parent_node = current($parent_node);
    $this->assertEquals('Banana', $parent_node->field_widget_reference->entity->label(), 'Child node has correct title.');
  }

}
