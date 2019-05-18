<?php
/**
 * @file
 * Contains Drupal\faircoin_address_field\Tests\FaircoinAddressFieldTest.
 */
namespace Drupal\faircoin_address_field\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the faircoin address field is configurable.
 *
 * @group Faircoin Address Field
 */
class FaircoinAddressFieldWebTest extends WebTestBase {

  /**
   * @var string
   */
  protected $contentTypeName;

  /**
   * @var AccountInterface
   */
  protected $administratorAccount;

  /**
   * @var AccountInterface
   */
  protected $authorAccount;

  /**
   * @var string
   */
  protected $fieldName;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'field_ui', 'faircoin_address_field'];

  public static function getInfo() {
    return array(
      'name' => 'FairCoin Address Field',
      'description' => 'Create a content type with faircoin_address field, create a node, check for correct values.',
      'group' => 'FairCoin Address Field',
    );
  }

  /**
   * {@inheritdoc}
   *
   * Once installed, a content type with the desired field is created.
   */
  public function setUp() {
    parent::setUp();

    // Create and login a user that creates the content type.
    $permissions = array(
      'administer content types',
      'administer node fields',
      'administer node form display',
    );
    $this->administratorAccount = $this->drupalCreateUser($permissions);
    parent::drupalLogin($this->administratorAccount);

    // Prepare a new content type where the field will be added.
    $this->contentTypeName = strtolower($this->randomMachineName(10));
    $this->drupalGet('admin/structure/types/add');
    $edit = array(
      'name' => $this->contentTypeName,
      'type' => $this->contentTypeName,
    );
    $this->drupalPostForm(NULL, $edit, t('Save and manage fields'));
    $this->assertText(t('The content type @name has been added.', array('@name' => $this->contentTypeName)));

    // Reset the permission cache.
    $create_permission = 'create ' . $this->contentTypeName . ' content';
    $this->checkPermissions(array($create_permission), TRUE);

    // Now that we have a new content type, create a user that has privileges
    // on the content type.
    $this->authorAccount = $this->drupalCreateUser(array($create_permission));
  }

  /**
   * Create a field on the content type created during setUp().
   *
   * @param string $type
   *   The storage field type to create
   * @param string $widget_type
   *   The widget to use when editing this field
   * @param int|string $cardinality
   *   Cardinality of the field. Use -1 to signify 'unlimited.'
   *
   * @return string
   *   Name of the field, like field_something
   */
  protected function createField($type = 'faircoin_address', $widget_type = 'faircoin_address_field_simple_text', $cardinality = '1') {
    $this->drupalGet('admin/structure/types/manage/' . $this->contentTypeName . '/fields');

    // Go to the 'Add field' page.
    //    $this->clickLink('Add field'); No link!
    $this->drupalGet('admin/structure/types/manage/' . $this->contentTypeName . '/fields/add-field');

    // Make a name for this field.
    $field_name = strtolower($this->randomMachineName(10));

    // Fill out the field form.
    $edit = array(
      'new_storage_type' => $type,
      'field_name' => $field_name,
      'label' => $field_name,
    );
    $this->drupalPostForm(NULL, $edit, t('Save and continue'));

    // Fill out the $cardinality form as if we're not using an unlimited number
    // of values.
    $edit = array(
      'cardinality' => 'number',
      'cardinality_number' => (string) $cardinality,
    );
    // If we have -1 for $cardinality, we should change the form's drop-down
    // from 'Number' to 'Unlimited.'
    if (-1 == $cardinality) {
      $edit = array(
        'cardinality' => '-1',
        'cardinality_number' => '1',
      );
    }

    // And now we save the cardinality settings.
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
    debug(
      t('Saved settings for field %field_name with widget %widget_type and cardinality %cardinality',
        array(
          '%field_name' => $field_name,
          '%widget_type' => $widget_type,
          '%cardinality' => $cardinality,
        )
      )
    );
    $this->assertText(t('Updated field @name field settings.', array('@name' => $field_name)));

    // Set the widget type for the newly created field.
    $this->drupalGet('admin/structure/types/manage/' . $this->contentTypeName . '/form-display');
    $edit = array(
      'fields[field_' . $field_name . '][type]' => $widget_type,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    return $field_name;
  }

  public function testFaicoinAddressFieldBasic() {

  // Add a single field as administrator user.
  $this->drupalLogin($this->administratorAccount);
  $this->fieldName = $this->createField('faircoin_address', 'faircoin_address_field_simple_text', '1');

  // Switch to the author user to create content with this type and field.
  $this->drupalLogin($this->authorAccount);
  $this->drupalGet('node/add/' . $this->contentTypeName);

  // Fill the create form.
  $title = 'test_title';
  $edit = array(
    'title[0][value]' => $title,
    'field_' . $this->fieldName . '[0][value]' => 'fairFRJYXxbyWBH2bEjR9sqYDixPPpJ5AX',
  );

    // Create the content.
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('@type @title has been created', array('@type' => $this->contentTypeName, '@title' => $title)));

    // Verify the value is shown when viewing this node.
    $field_p = $this->xpath("//code[contains(@class,'faircoin-address-qrcode-text')]/text()");
    $this->assertEqual((string) $field_p[0], "fairFRJYXxbyWBH2bEjR9sqYDixPPpJ5AX");

  }

}

