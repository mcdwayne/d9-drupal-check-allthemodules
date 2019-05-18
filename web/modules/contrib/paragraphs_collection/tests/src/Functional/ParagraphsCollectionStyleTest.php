<?php

namespace Drupal\Tests\paragraphs_collection\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\LoginAdminTrait;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;

/**
 * Test paragraphs collection style behavior.
 *
 * @group paragraphs_collection
 */
class ParagraphsCollectionStyleTest extends BrowserTestBase {

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

    $edit = [
      'styles[bold][enabled]' => TRUE,
      'styles[italic][enabled]' => FALSE,
      'styles[regular][enabled]' => FALSE,
      'styles[underline][enabled]' => FALSE
    ];
    $this->drupalPostForm('admin/reports/paragraphs_collection/styles', $edit, t('Save configuration'));

    $this->addParagraphsType('testplugin');
    $paragraph_type = \Drupal::configFactory()->getEditable('paragraphs.paragraphs_type.testplugin');
    $paragraph_type->set('behavior_plugins.lockable.enabled', TRUE);
    $paragraph_type->set('behavior_plugins.style.enabled', TRUE);
    $paragraph_type->set('behavior_plugins.style.groups.regular_test_group.default', 'bold');
    $paragraph_type->save();
    $this->addFieldtoParagraphType('testplugin', 'field_text', 'text');
    $this->addParagraphedContentType('testcontent', 'testparagraphfield');
    $this->drupalGet('node/add/testcontent');
    $this->getSession()->getPage()->pressButton('Add testplugin');
    $this->getSession()->getPage()->pressButton('Add testplugin');
    $this->assertSession()->fieldExists('testparagraphfield[0][subform][field_text][0][value]');
    $this->assertSession()->fieldExists('testparagraphfield[1][subform][field_text][0][value]');
    $edit = [
      'title[0][value]' => 'Example title',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->pageTextContains('testcontent Example title has been created.');
    $this->assertSession()->responseContains('paragraphs-behavior-style--bold');
  }

  /**
   * Test paragraphs style behavior plugin summary.
   */
  public function testStylePluginSummary() {
    // Create a paragraphed content type.
    $this->addParagraphedContentType('testcontent', 'testparagraphfield');
    $this->loginAsAdmin([
      'access content overview',
      'edit behavior plugin settings',
      'create testcontent content',
      'edit any testcontent content',
    ]);

    // Enable plugins.
    $edit = [
      'styles[bold][enabled]' => TRUE,
      'styles[italic][enabled]' => TRUE,
      'styles[regular][enabled]' => FALSE,
      'styles[underline][enabled]' => FALSE
    ];
    $this->drupalPostForm('admin/reports/paragraphs_collection/styles', $edit, t('Save configuration'));
    // Create a paragraph type and enable behavior plugins.
    $this->addParagraphsType('testplugin');
    $paragraph_type = \Drupal::configFactory()->getEditable('paragraphs.paragraphs_type.testplugin');
    $paragraph_type->set('behavior_plugins.style.enabled', TRUE);
    $paragraph_type->set('behavior_plugins.style.groups.regular_test_group.default', '');
    $paragraph_type->save();
    $this->addFieldtoParagraphType('testplugin', 'field_text', 'text');
    $this->setParagraphsWidgetSettings('testcontent', 'testparagraphfield', ['edit_mode' => 'closed', 'closed_mode' => 'summary']);
    // Create a Paragraph in a node selecting one of the enabled plugins.
    $this->drupalGet('node/add/testcontent');
    $this->getSession()->getPage()->pressButton('Add testplugin');
    $this->getSession()->getPage()->selectFieldOption('testparagraphfield[0][behavior_plugins][style][style_wrapper][styles][regular_test_group]', 'bold');
    $this->assertSession()->fieldExists('testparagraphfield[0][subform][field_text][0][value]');
    $edit = [
      'title[0][value]' => 'Example title',
      'testparagraphfield[0][subform][field_text][0][value]' => 'test',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->pageTextContains('testcontent Example title has been created.');
    // Disable the selected plugin.
    $edit = [
      'styles[bold][enabled]' => FALSE,
    ];
    $this->drupalPostForm('admin/reports/paragraphs_collection/styles', $edit, t('Save configuration'));
    // Edit the node and check if the summary is present.
    $node = $this->getNodeByTitle('Example title');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('paragraphs-collapsed-description');
  }

}
