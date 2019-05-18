<?php

namespace Drupal\Tests\inline_formatter_field\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the creation of inline formatter fields.
 *
 * @group inline_formatter_field
 */
class InlineFormatterFieldTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'node',
    'inline_formatter_field',
  ];

  /**
   * A user with permission to create articles.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article']);
    $this->webUser = $this->drupalCreateUser([
      'create article content',
      'edit own article content',
      'edit inline formmater field settings',
    ]);
    $this->drupalLogin($this->webUser);

    // Add the inline formatter field to the article content type.
    FieldStorageConfig::create([
      'field_name' => 'field_inline_formatter_field',
      'entity_type' => 'node',
      'type' => 'inline_formatter_field',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_inline_formatter_field',
      'label' => 'Inline Formatter Field',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();

    entity_get_form_display('node', 'article', 'default')
      ->setComponent('field_inline_formatter_field', [
        'type' => 'inline_formatter_field_widget',
      ])
      ->save();

    entity_get_display('node', 'article', 'default')
      ->setComponent('field_inline_formatter_field', [
        'type' => 'inline_formatter_field_formatter',
        'weight' => 1,
        'settings' => [
          'formatted_field' => '<h1>Test</h1>',
        ],
      ])
      ->save();
  }

  /**
   * Test to confirm the widget is setup.
   *
   * @covers \Drupal\inline_formatter_field\Plugin\Field\FieldWidget\InlineFormatterFieldWidget::formElement
   */
  public function testFieldWidget() {
    $this->drupalGet('node/add/article');
    $this->assertSession()->fieldValueEquals("field_inline_formatter_field[0][display_format]", '');
  }

  /**
   * Test the formatter.
   *
   * @covers \Drupal\inline_formatter_field\Plugin\Field\FieldFormatter\InlineFormatterFieldFormatter::viewElements
   *
   * @dataProvider providerValues
   */
  public function testInlineFormatterFieldFormatter($input) {
    // Test basic entry of inline formatter field.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'field_inline_formatter_field[0][display_format]' => $input,
    ];

    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    if ($input) {
      $this->assertSession()->responseContains('<h1>Test</h1>');
    }
    else {
      $this->assertSession()->responseNotContains('<h1>Test</h1>');
    }
  }

  /**
   * Provides values to test.
   */
  public function providerValues() {
    return [
      'field is enabled' => [TRUE],
      'field is not enabled' => [FALSE],
    ];
  }

  /**
   * Test to confirm the settings form is setup.
   *
   * @covers \Drupal\inline_formatter_field\Form\SettingsForm::buildForm
   */
  public function testSettingsForm() {
    $this->drupalGet('admin/config/inline_formatter_field/settings');
    $this->assertSession()->fieldValueEquals("ace_source", 'cdn');
    $this->assertSession()->fieldValueEquals("fa_source", 'cdn');
    $this->assertSession()->fieldValueEquals("ace_theme", 'monokai');
    $this->assertSession()->fieldValueEquals("ace_mode", 'twig');
    $this->assertSession()->fieldValueEquals("ace_wrap", 1);
    $this->assertSession()->fieldValueEquals("ace_print_margin", 1);
  }

}
