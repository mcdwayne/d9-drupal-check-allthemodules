<?php

namespace Drupal\options_table\Tests;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field\Functional\FieldTestBase;

/**
 * Tests the Options Table widgets.
 *
 * @group options
 */
class OptionsTableWidgetsTest extends FieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'options', 'entity_test', 'options_test', 'taxonomy', 'field_ui', 'options_table'];

  /**
   * A field storage with cardinality 1 to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $card1;

  /**
   * A field storage with cardinality 2 to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $card2;

  /**
   * The web assert object.
   *
   * @var \Drupal\Tests\WebAssert
   */
  protected $assertSession;

  protected function setUp() {
    parent::setUp();

    $this->assertSession = $this->assertSession();

    // Field storage with cardinality 1.
    $this->card1 = FieldStorageConfig::create([
      'field_name' => 'card_1',
      'entity_type' => 'entity_test',
      'type' => 'list_integer',
      'cardinality' => 1,
      'settings' => [
        'allowed_values' => [
          // Make sure that 0 works as an option.
          0 => 'Zero',
          1 => 'One',
          // Make sure that option text is properly sanitized.
          2 => 'Some <script>dangerous</script> & unescaped <strong>markup</strong>',
          // Make sure that HTML entities in option text are not double-encoded.
          3 => 'Some HTML encoded markup with &lt; &amp; &gt;',
        ],
      ],
    ]);
    $this->card1->save();

    // Field storage with cardinality 2.
    $this->card2 = FieldStorageConfig::create([
      'field_name' => 'card_2',
      'entity_type' => 'entity_test',
      'type' => 'list_integer',
      'cardinality' => 2,
      'settings' => [
        'allowed_values' => [
          // Make sure that 0 works as an option.
          0 => 'Zero',
          1 => 'One',
          // Make sure that option text is properly sanitized.
          2 => 'Some <script>dangerous</script> & unescaped <strong>markup</strong>',
        ],
      ],
    ]);
    $this->card2->save();

    // Create a web user.
    $this->drupalLogin($this->drupalCreateUser(['view test entity', 'administer entity_test content']));
  }

  /**
   * Tests the 'options_table' widget (single select).
   */
  public function testRadioButtons() {
    // Create an instance of the 'single value' field.
    $field = FieldConfig::create([
      'field_storage' => $this->card1,
      'bundle' => 'entity_test',
    ]);
    $field->save();
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($this->card1->getName(), [
        'type' => 'options_table',
      ])
      ->save();

    // Create an entity.
    $entity = EntityTest::create([
      'user_id' => 1,
      'name' => $this->randomMachineName(),
    ]);
    $entity->save();
    $entity_init = clone $entity;

    // With no field data, no buttons are checked.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertSession->checkboxNotChecked('edit-card-1-table-enabled-0');
    $this->assertSession->checkboxNotChecked('edit-card-1-table-enabled-1');
    $this->assertSession->checkboxNotChecked('edit-card-1-table-enabled-2');
    $this->assertSession->responseContains('Some dangerous &amp; unescaped <strong>markup</strong>');
    $this->assertSession->responseContains('Some HTML encoded markup with &lt; &amp; &gt;');

    // Select first option.
    $edit = ['card_1[table][enabled]' => 0];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_1', [0]);

    // Check that the selected button is checked.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertSession->checkboxChecked('edit-card-1-table-enabled-0');
    $this->assertSession->checkboxNotChecked('edit-card-1-table-enabled-1');
    $this->assertSession->checkboxNotChecked('edit-card-1-table-enabled-2');

    // Unselect option.
    $edit = ['card_1[table][enabled]' => '_none'];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_1', []);

    // Check that required radios with one option is auto-selected.
    $this->card1->setSetting('allowed_values', [99 => 'Only allowed value']);
    $this->card1->save();
    $field->setRequired(TRUE);
    $field->save();
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertSession->checkboxChecked('edit-card-1-table-enabled-99');
  }

  /**
   * Tests the 'options_table' widget (multiple select).
   */
  public function testCheckBoxes() {
    // Create an instance of the 'multiple values' field.
    $field = FieldConfig::create([
      'field_storage' => $this->card2,
      'bundle' => 'entity_test',
    ]);
    $field->save();
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($this->card2->getName(), [
        'type' => 'options_table',
      ])
      ->save();

    // Create an entity.
    $entity = EntityTest::create([
      'user_id' => 1,
      'name' => $this->randomMachineName(),
    ]);
    $entity->save();
    $entity_init = clone $entity;

    // Display form: with no field data, nothing is checked.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertSession->checkboxNotChecked('edit-card-2-table-0-enabled');
    $this->assertSession->checkboxNotChecked('edit-card-2-table-1-enabled');
    $this->assertSession->checkboxNotChecked('edit-card-2-table-2-enabled');
    $this->assertSession->responseContains('Some dangerous &amp; unescaped <strong>markup</strong>');

    // Submit form: select first and third options.
    $edit = [
      'card_2[table][0][enabled]' => TRUE,
      'card_2[table][1][enabled]' => FALSE,
      'card_2[table][2][enabled]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', [0, 2]);

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertSession->checkboxChecked('edit-card-2-table-0-enabled');
    $this->assertSession->checkboxNotChecked('edit-card-2-table-1-enabled');
    $this->assertSession->checkboxChecked('edit-card-2-table-2-enabled');

    // Submit form: select only first option.
    $edit = [
      'card_2[table][0][enabled]' => TRUE,
      'card_2[table][1][enabled]' => FALSE,
      'card_2[table][2][enabled]' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', [0]);

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertSession->checkboxChecked('edit-card-2-table-0-enabled');
    $this->assertSession->checkboxNotChecked('edit-card-2-table-1-enabled');
    $this->assertSession->checkboxNotChecked('edit-card-2-table-2-enabled');

    // Submit form: select the three options while the field accepts only 2.
    $edit = [
      'card_2[table][0][enabled]' => TRUE,
      'card_2[table][1][enabled]' => TRUE,
      'card_2[table][2][enabled]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession->pageTextContains('this field cannot hold more than 2 values');

    // Submit form: uncheck all options.
    $edit = [
      'card_2[table][0][enabled]' => FALSE,
      'card_2[table][1][enabled]' => FALSE,
      'card_2[table][2][enabled]' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Check that the value was saved.
    $this->assertFieldValues($entity_init, 'card_2', []);

    // Required checkbox with one option is auto-selected.
    $this->card2->setSetting('allowed_values', [99 => 'Only allowed value']);
    $this->card2->save();
    $field->setRequired(TRUE);
    $field->save();
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertSession->checkboxChecked('edit-card-2-table-99-enabled');
  }

  /**
   * Tests the 'options_table' widget (multiple select) sorting.
   */
  public function testCheckBoxesWeight() {
    // Create an instance of the 'multiple values' field.
    $field = FieldConfig::create([
      'field_storage' => $this->card2,
      'bundle' => 'entity_test',
    ]);
    $field->save();
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($this->card2->getName(), [
        'type' => 'options_table',
      ])
      ->save();

    // Create an entity.
    $entity = EntityTest::create([
      'user_id' => 1,
      'name' => $this->randomMachineName(),
    ]);
    $entity->save();
    $entity_init = clone $entity;

    // Display form: with no field data, nothing is checked.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');

    // Submit form: select first and third options.
    $edit = [
      'card_2[table][0][enabled]' => TRUE,
      'card_2[table][1][enabled]' => FALSE,
      'card_2[table][2][enabled]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', [0, 2]);

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertSession->checkboxChecked('edit-card-2-table-0-enabled');
    $this->assertSession->checkboxNotChecked('edit-card-2-table-1-enabled');
    $this->assertSession->checkboxChecked('edit-card-2-table-2-enabled');
    $this->assertOptionSelected('edit-card-2-table-0-weight', 0);
    $this->assertOptionSelected('edit-card-2-table-2-weight', 1);
    $this->assertOptionSelected('edit-card-2-table-1-weight', 2);

    // Change delta sorting.
    $edit = [
      "card_2[table][2][weight]" => -1,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', [2, 0]);

    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertSession->checkboxChecked("edit-card-2-table-0-enabled");
    $this->assertSession->checkboxNotChecked("edit-card-2-table-1-enabled");
    $this->assertSession->checkboxChecked("edit-card-2-table-2-enabled");
    $this->assertOptionSelected('edit-card-2-table-2-weight', 0);
    $this->assertOptionSelected('edit-card-2-table-0-weight', 1);
    $this->assertOptionSelected('edit-card-2-table-1-weight', 2);

    // Changes options and sorting at the same time.
    $edit = [
      "card_2[table][0][enabled]" => TRUE,
      "card_2[table][1][enabled]" => TRUE,
      "card_2[table][2][enabled]" => FALSE,
      "card_2[table][2][weight]" => -3,
      "card_2[table][1][weight]" => -2,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', [1, 0]);

    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertSession->checkboxChecked("edit-card-2-table-0-enabled");
    $this->assertSession->checkboxChecked("edit-card-2-table-1-enabled");
    $this->assertSession->checkboxNotChecked("edit-card-2-table-2-enabled");
    $this->assertOptionSelected('edit-card-2-table-1-weight', 0);
    $this->assertOptionSelected('edit-card-2-table-0-weight', 1);
    $this->assertOptionSelected('edit-card-2-table-2-weight', 2);
  }

}
