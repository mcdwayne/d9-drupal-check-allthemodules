<?php

namespace Drupal\Tests\paragraphs_collection\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\LoginAdminTrait;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;

/**
 * Test paragraphs collection style behavior.
 *
 * @group paragraphs_collection
 */
class ParagraphsCollectionStyleTest extends JavascriptTestBase {

  use LoginAdminTrait;
  use ParagraphsTestBaseTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'paragraphs_collection_test',
    'paragraphs_collection',
    'field',
    'field_ui',
    'block',
    'link',
    'text',
  ];

  /**
   * Test paragraphs style behavior plugin.
   */
  public function testStylePlugin() {
    $this->loginAsAdmin([
      'access content overview',
      'edit behavior plugin settings'
    ]);

    $this->drupalGet('admin/structure/paragraphs_type/add');
    $this->click('#edit-behavior-plugins-style-enabled');
    $this->click('#edit-behavior-plugins-style-settings-groups-regular-test-group');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $edit = [
      'label' => 'TestPlugin',
      'id' => 'testplugin',
      'behavior_plugins[style][settings][groups][regular_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups_defaults][regular_test_group][default]' => '',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and manage fields'));
    $this->addParagraphedContentType('testcontent', 'testparagraphfield');
    $this->drupalGet('node/add/testcontent');
    $this->click('.dropbutton-toggle');
    $this->getSession()->getPage()->pressButton('Add TestPlugin');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->clickLink('Behavior');
    $style_selector = $this->getSession()->getPage()->find('css', '.form-item-testparagraphfield-0-behavior-plugins-style-style-wrapper-styles-regular-test-group');
    $this->assertTrue($style_selector->isVisible());
    $this->clickLink('Content');
    $this->assertFalse($style_selector->isVisible());
  }

}
