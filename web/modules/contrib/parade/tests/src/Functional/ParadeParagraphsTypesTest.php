<?php

namespace Drupal\Tests\parade\Functional;

require_once __DIR__ . '/Includes/ParagraphsTypesExpectedData.inc';

/**
 * Tests that the Paragraphs types exists.
 *
 * @todo: Add tests for 'Form display'.
 * @todo: Include view and form descriptions (e.g Default vs Custom render for text boxes).
 * @todo: Add stricter array comparison/better 'get text from dom'.
 * @todo: Check if a field is disabled or not.
 * @todo: Marketo Form + Poll.
 *
 * @group parade
 */
class ParadeParagraphsTypesTest extends ParadeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'parade',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a user with permissions to manage the paragraphs types.
    $permissions = [
      'administer paragraphs types',
      'administer site configuration',
      'administer paragraph fields',
      'administer paragraph display',
      'administer paragraph form display',
      'administer site configuration',
    ];
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);
  }

  /**
   * Tests that the Paragraphs types exist.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testParagraphTypesPage() {
    $this->drupalGet('admin/structure/paragraphs_type');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Paragraphs types');

    $actualData = $this->getFieldsTableBodyAsArray('//table');

    $this->assertArraysAreEqual(get_parade_paragraphs_types_list(), $actualData);
  }

  /**
   * Generic 'Check type' function.
   *
   * @param string $type
   *   Paragraph type machine name.
   * @param array $expectedFields
   *   Expected fields.
   * @param array $expectedViews
   *   Expected view displays.
   * @param array $expectedForms
   *   Expected form displays.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  private function checkParagraphTypeConfig($type, array $expectedFields, array $expectedViews, array $expectedForms) {
    $this->drupalGet('admin/structure/paragraphs_type/' . $type . '/fields');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Manage fields');

    $actualFields = $this->getFieldsTableBodyAsArray('//table');
    $this->assertArraysAreEqual($expectedFields, $actualFields, $type . '/fields');

    foreach ($expectedViews as $viewMode => $expectedView) {
      $mode = ($viewMode === 'default') ? '' : ('/' . $viewMode);

      $this->drupalGet('admin/structure/paragraphs_type/' . $type . '/display' . $mode);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->pageTextContains('Manage display');

      $actualView = $this->getViewsTableAsArray('//table');
      $this->assertArraysAreEqual($expectedView, $actualView, $type . '/display:' . $viewMode);
    }

    // @todo.
    // foreach ($expectedForms as $formMode => $expectedForm) {
    // }
  }

  /**
   * Check config for 'Header'.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  private function checkHeaderParagraphTypeConfig() {
    $typeData = get_parade_header_paragraphs_type_data();
    $this->checkParagraphTypeConfig('header', $typeData['fields'], $typeData['views'], $typeData['forms']);
  }

  /**
   * Check config for 'Images'.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  private function checkImagesParagraphTypeConfig() {
    $typeData = get_parade_images_paragraphs_type_data();
    $this->checkParagraphTypeConfig('images', $typeData['fields'], $typeData['views'], $typeData['forms']);
  }

  /**
   * Check config for 'Locations'.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  private function checkLocationsParagraphTypeConfig() {
    $typeData = get_parade_locations_paragraphs_type_data();
    $this->checkParagraphTypeConfig('locations', $typeData['fields'], $typeData['views'], $typeData['forms']);
  }

  /**
   * Check config for 'Parallax'.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  private function checkParallaxParagraphTypeConfig() {
    $typeData = get_parade_parallax_paragraphs_type_data();
    $this->checkParagraphTypeConfig('parallax', $typeData['fields'], $typeData['views'], $typeData['forms']);
  }

  /**
   * Check config for 'Simple'.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  private function checkSimpleParagraphTypeConfig() {
    $typeData = get_parade_simple_paragraphs_type_data();
    $this->checkParagraphTypeConfig('simple', $typeData['fields'], $typeData['views'], $typeData['forms']);
  }

  /**
   * Check config for 'Social Links'.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  private function checkSocialLinksParagraphTypeConfig() {
    $typeData = get_parade_social_links_paragraphs_type_data();
    $this->checkParagraphTypeConfig('social_links', $typeData['fields'], $typeData['views'], $typeData['forms']);
  }

  /**
   * Check config for 'Text & Image'.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  private function checkTextAndImageParagraphTypeConfig() {
    $typeData = get_parade_image_text_paragraphs_type_data();
    $this->checkParagraphTypeConfig('image_text', $typeData['fields'], $typeData['views'], $typeData['forms']);
  }

  /**
   * Check config for 'Text Box'.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  private function checkTextBoxParagraphTypeConfig() {
    $typeData = get_parade_text_box_paragraphs_type_data();
    $this->checkParagraphTypeConfig('text_box', $typeData['fields'], $typeData['views'], $typeData['forms']);
  }

  /**
   * Check config for 'Text Boxes'.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  private function checkTextBoxesParagraphTypeConfig() {
    $typeData = get_parade_text_boxes_paragraphs_type_data();
    $this->checkParagraphTypeConfig('text_boxes', $typeData['fields'], $typeData['views'], $typeData['forms']);
  }

  /**
   * Tests that the Paragraphs types are configured correctly.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testParagraphTypesConfiguration() {
    $this->checkHeaderParagraphTypeConfig();
    $this->checkImagesParagraphTypeConfig();
    $this->checkLocationsParagraphTypeConfig();
    $this->checkParallaxParagraphTypeConfig();
    $this->checkSimpleParagraphTypeConfig();
    $this->checkSocialLinksParagraphTypeConfig();
    $this->checkTextAndImageParagraphTypeConfig();
    $this->checkTextBoxParagraphTypeConfig();
    $this->checkTextBoxesParagraphTypeConfig();
  }

}
