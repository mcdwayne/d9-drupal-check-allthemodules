<?php

namespace Drupal\Tests\toggle_editable_fields\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\toggle_editable_fields\Plugin\Field\FieldFormatter\ToggleEditableFormatter;

/**
 * Tests the Form mode manager user interfaces.
 *
 * @group toggle_editable_fields
 */
class ToggleEditableFieldsUiTest extends BrowserTestBase {

  use FieldUiTestTrait;

  /**
   * Stores the node content used by this test.
   *
   * @var array
   */
  public $nodes;

  /**
   * Node entity type to test.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType1;

  /**
   * Common modules to install for form_mode_manager.
   *
   * @var string[]
   */
  public static $modules = [
    'block',
    'entity_test',
    'field_ui',
    'node',
    'user',
    'toggle_editable_fields',
    'taxonomy',
  ];

  /**
   * A user that can edit content types.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Contains all data about created field for this test.
   *
   * @var array
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add a content type.
    $this->nodeType1 = $this->drupalCreateContentType();

    $this->adminUser = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer node display',
    ]);

    $this->drupalLogin($this->adminUser);
    $this->drupalPlaceBlock('system_breadcrumb_block');

    // Add a boolean field to the newly-created type.
    $label = $this->randomMachineName();
    $field_name = Unicode::strtolower($label);
    $this->createBooleanField($label, $field_name, $this->nodeType1->id(), 'toggle_editable_formatter', [], [], ['label' => 'hidden', 'region' => 'content']);

    $this->drupalGet("admin/structure/types/manage/{$this->nodeType1->id()}/display");
    $edit = ["display_modes_custom[full]" => TRUE];
    $this->drupalPostForm("admin/structure/types/manage/{$this->nodeType1->id()}/display", $edit, t('Save'));

    // Generate contents to this tests.
    for ($i = 0; $i < 50; $i++) {
      $this->nodes[] = $this->createNode(['type' => $this->nodeType1->id()]);
    }
  }

  /**
   * Tests the boolean formatter field UI.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testFieldUi() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();
    $default_settings = [
      'on' => 'On',
      'off' => 'Off',
      'size' => 'small',
      'onstyle' => 'success',
      'offstyle' => 'default',
    ];

    $this->drupalLogin($this->rootUser);
    $this->drupalGet("admin/structure/types/manage/{$this->nodeType1->id()}/display");
    $assert_session->statusCodeEquals(200);
    $this->assertDefaultFieldSettings();

    $this->drupalGet("admin/structure/types/manage/{$this->nodeType1->id()}/display/full");
    $assert_session->statusCodeEquals(200);
    $this->assertDefaultFieldSettings();

    $this->drupalGet("/node/{$this->nodes[1]->id()}/edit");
    $page->fillField("{$this->field['name']}[value]", 1);
    $page->pressButton('Save');
    $assert_session->statusCodeEquals(200);

    $toggle_checkbox = $this->assertSession()
      ->elementExists('xpath', '//input[contains(@data-toggle, "toggle")]');

    foreach (array_keys($default_settings) as $key) {
      $this->assertTrue($toggle_checkbox->hasAttribute("data-$key"), new FormattableMarkup('Default data attribute %key found.', ['%key' => "data-$key"]));
    }
  }

  /**
   * Assert default field settings are correctly set.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function assertDefaultFieldSettings() {
    foreach (ToggleEditableFormatter::defaultSettings() as $value) {
      $this->assertSession()->pageTextContains($value);
    }
  }

  /**
   * Create a new boolean field configured to use our formatter.
   *
   * @param string $label
   *   The label of the new field. Defaults to a random string.
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $type_name
   *   The node type that this field will be added to.
   * @param string $widget_name
   *   The name of the widget.
   * @param array $storage_settings
   *   A list of field storage settings that will be added to the defaults.
   * @param array $field_settings
   *   A list of instance settings that will be added to the instance defaults.
   * @param array $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  protected function createBooleanField($label, $name, $type_name, $widget_name, array $storage_settings = [], array $field_settings = [], array $widget_settings = []) {
    $type_path = 'admin/structure/types/manage/' . $type_name;
    $this->fieldUIAddNewField($type_path, $name, $label, 'boolean', $storage_settings, $field_settings);

    $this->field = ['name' => "field_" . $name, 'label' => $label];
    $widget_settings += ['type' => $widget_name];

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display */
    $view_display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->load('node.' . $type_name . '.default');
    $view_display->setComponent($this->field['name'], $widget_settings)->save();
  }

}
