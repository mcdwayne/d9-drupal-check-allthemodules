<?php

namespace Drupal\paragraphs_collection\Tests;
use Drupal\paragraphs\Tests\Experimental\ParagraphsExperimentalTestBase;

/**
 * Tests the Paragraphs Collection paragraph types.
 *
 * @group paragraphs_collection
 * @requires module paragraphs
 */
class ParagraphsTypesTest extends ParagraphsExperimentalTestBase {

  /**
   * Modules to be enabled.
   */
  public static $modules = [
    'paragraphs_collection',
    'paragraphs_library',
  ];

  /**
   * Tests adding the existing paragraph types.
   */
  public function testAddParagraphTypes() {
    $this->addParagraphedContentType('paragraphed_test');
    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'administer paragraphs library',
    ]);
    $this->drupalGet('/node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_intro_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_quote_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_separator_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_subtitle_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_title_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_user_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_footer_add_more');

    $edit = [
      'title[0][value]' => 'Paragraph types example',
      'field_paragraphs[0][subform][paragraphs_text][0][value]' => 'Intro test',
      'field_paragraphs[1][subform][paragraphs_quote_text][0][value]' => 'Quote test',
      'field_paragraphs[1][subform][paragraphs_quote_author][0][value]' => 'Author test',
      'field_paragraphs[3][subform][paragraphs_subtitle][0][value]' => 'Subtitle test',
      'field_paragraphs[4][subform][paragraphs_title][0][value]' => 'Title test',
      'field_paragraphs[5][subform][paragraphs_user][0][target_id]' => $this->admin_user->getUsername() . ' (' . $this->admin_user->id() . ')',
      'field_paragraphs[6][subform][paragraphs_text][0][value]' => 'Footer test',
    ];

    $this->drupalPostForm(NULL, $edit, 'Save');

    // Checks content.
    $this->assertText('Intro test');
    $this->assertText($this->admin_user->getUsername());
    $this->assertText('Footer test');
    $this->assertText('Subtitle test');
    $this->assertText('Title test');

    // Asserts the quote paragraph type.
    $elements = $this->xpath('//blockquote[contains(@class, class)]', [':class' => 'paragraph--type--quote']);
    $this->assertEqual(count($elements), 1);
    $this->assertText('Quote test');
    $this->assertText('Author test');

    // Adds the link paragraph type.
    $this->drupalGet('/node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_link_add_more');
    $edit = [
      'title[0][value]' => 'Link example',
      'field_paragraphs[0][subform][paragraphs_link][0][uri]' => 'Paragraph types example (1)',
      'field_paragraphs[0][subform][paragraphs_link][0][title]' => 'Link test',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Checks if the link type is working properly.
    $this->clickLink('Link test');
    $this->assertText('Paragraph types example');

  }

  /**
   * Ensures that a new paragraph type is created.
   */
  public function testCreateParagraphType() {
    $this->loginAsAdmin();
    $this->drupalGet('/admin/structure/paragraphs_type');
    $this->clickLink(t('Add paragraph type'));
    $edit = [
      'label' => 'test_paragraph',
      'id' => 'test_paragraph',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and manage fields'));
    $this->assertText('Saved the test_paragraph Paragraphs type');
  }

}
