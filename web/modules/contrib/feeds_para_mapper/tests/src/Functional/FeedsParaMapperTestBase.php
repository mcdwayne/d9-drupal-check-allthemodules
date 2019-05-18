<?php

namespace Drupal\Tests\feeds_para_mapper\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test basic functionality via BrowserTestBase.
 * @todo: tests are failing because some dependencies modules use PrivateTempStore, see https://www.drupal.org/project/feeds/issues/3012342
 * @todo: for now we applied the patch in the issue, waiting for module update
 *
 */
abstract class FeedsParaMapperTestBase extends BrowserTestBase {

  protected $webUser;
  protected $profile = 'testing';
  protected $bundles;
  protected $contentType;
  protected $feedType;
  protected $paragraphField;
  protected $importer;
  protected $multiValued = FALSE;
  protected $multiValuedParagraph = FALSE;
  protected $createdPlugins;
  protected $nested = FALSE;


  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array(
    'node',
    'field',
    'field_ui',
    'image',
    'paragraphs',
    'feeds',
    'feeds_para_mapper',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $args = func_get_args();
    if (isset($args[0])) {
      $this->multiValued = $args[0];
    }
    if (isset($args[1])) {
      $this->multiValuedParagraph = $args[1];
    }
    if (isset($args[2])) {
      $this->nested = $args[2];
    }
    $permissions = array(
      'bypass node access',
      'administer nodes',
      'administer feeds',
      'administer feeds_feed fields',
      'administer content types',
      'administer paragraphs types',
      'administer paragraph fields',
      'administer node fields',
    );
    // If we are testing multi valued fields, load Feeds Tamper module:
    if ($this->multiValued || $this->multiValuedParagraph) {
      self::$modules[] = "feeds_tamper";
      self::$modules[] = "feeds_tamper_ui";
      $permissions[] = "administer feeds_tamper";
    }
    parent::setUp();
    $this->webUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->webUser);
    // Create paragraphs bundles:
    $this->createBundles();
    // Create a content type with a paragraph field:
    $this->contentType = "product";
    $this->paragraphField = "details";
    $last_key = count($this->bundles) - 1;
    $last_bundle = array($this->bundles[$last_key]['name']);
    $this->createCT($this->contentType, $this->paragraphField, $last_bundle);
    $this->feedType = "product_feed";
    $this->createFeedType($this->contentType, $this->feedType);
  }

  /**
   * Creates the needed Paragraphs bundles in a loop.
   *
   * 1- Create a bundle with a text field.
   * 2- Create another bundle with a paragraph field,
   * with the previous bundle as allowed.
   */
  protected function createBundles() {
    $counter = $this->nested ? 2 : 1;
    for ($i = 0; $i < $counter; $i++) {
      if ($i === 0) {
        $cardinality = $this->multiValued ? -1 : 1;
        $bundle = array(
          'name' => 'image_details_bundle',
          'fields' => array(
            array(
              'name' => 'description',
              'type' => "text",
              'widget' => 'text_textfield',
              'cardinality' => $cardinality,
              'mapping' => array(
                'text'  => 'field_description',
              ),
              'mapping_multiple' => array(
                'text_multiple'  => 'field_description',
              ),
            ),
            array(
              'name' => 'image',
              'type' => "image",
              'widget' => 'image_image',
              'cardinality' => $cardinality,
              'mapping' => array(
                'image_alt' => 'field_image:alt',
                'image_title' => 'field_image:title',
                'image_uri' => 'field_image:uri',
              ),
              'mapping_multiple' => array(
                'image_multi_alt' => 'field_image:alt',
                'image_multi_title' => 'field_image:title',
                'image_multi_uri' => 'field_image:uri',
              ),
            ),
          ),
        );
      }
      else {
        $isLast = ($i + 1) === $counter;
        $cardinality = $this->multiValuedParagraph && $isLast ? -1 : 1;
        $bundle = array(
          'name' => 'image_bundle',
          'fields' => array(
            array(
              'name' => 'images',
              'type' => "paragraphs",
              'widget' => 'paragraphs_embed',
              'cardinality' => $cardinality,
              'allowed_bundles' => array(end($this->bundles)['name']),
            ),
          ),
        );
      }
      $this->bundles[] = $bundle;
      $this->createBundle($bundle);
    }
  }

  /**
   * Utility function to create a content type.
   *
   * @param string $name
   *   The content type name.
   * @param string $paragraph_field
   *   The paragraph field name to add to the content type.
   * @param array $allowed_bundles
   *   The allowed bundles for the paragraph field.
   */
  protected function createCT($name, $paragraph_field, array $allowed_bundles) {
    parent::createContentType(array('type' => $name));
    $fields = array();
    $cardinality = $this->multiValuedParagraph ? -1 : 1;
    $fields[$paragraph_field] = array(
      'type' => "field_ui:entity_reference_revisions:paragraph",
      'widget' => 'paragraphs_embed',
      'cardinality' => $cardinality,
      'bundles' => $allowed_bundles,
    );
    $path = "admin/structure/types/manage/{$name}/fields/add-field";
    foreach ($fields as $field_name => $details) {
      $this->createField($path,$field_name, $details['cardinality'], $details['type'], $details['widget'], $details['bundles']);
    }
  }

  protected function createFeedType($contentType, $feedType){
    $this->drupalGet('admin/structure/feeds/add');
    $edit = array(
      'id' => $feedType,
      'label' => $feedType,
      'description' => $feedType,
      'fetcher' => 'upload',
      'parser' => 'csv',
      'processor' => 'entity:node',
      'processor_wrapper[advanced][values][type]' => $contentType,
    );
    $this->drupalPostForm(null, $edit, t("Save and add mappings"));
    //@todo: error on save "The referenced entity (user: 0) does not exist"
  }

  /**
   * Creates a paragraph bundle.
   *
   * @param array $bundle
   *   Includes the bundle name and the field details.
   */
  protected function createBundle(array $bundle) {
    $this->drupalGet('admin/structure/paragraphs_type/add');
    $edit = array(
      'label' => $bundle['name'],
      'id' => $bundle['name'],
    );
    $this->drupalPostForm(NULL, $edit, t('Save and manage fields'));
    #$message = format_string("Created the paragraph bundle @name.", array('@name' => $bundle['name']));
    $text = t("Saved the @name Paragraphs type.", array('@name' => $bundle['name']));
    $this->assertSession()->pageTextContains($text);

    // Add A field to the bundle:
    $fields = $bundle['fields'];
    foreach ($fields as $field) {
      $allowed_bundles = array();
      if (isset($field['allowed_bundles'])) {
        $allowed_bundles = $field['allowed_bundles'];
      }
      $path = "admin/structure/paragraphs_type/{$bundle['name']}/fields/add-field";
      $this->createField($path,$field['name'], $field['cardinality'], $field['type'], $field['widget'], $allowed_bundles);
    }
  }

  /**
   * Utility function to create fields on a content type/paragraph bundle.
   *
   * @param string $field_name
   *   Name of the field, like field_something.
   * @param int $cardinality
   *   Cardinality.
   * @param string $type
   *   Field type.
   * @param string $widget
   *   Field widget.
   * @param array $bundles
   *   The allowed bundles if it's a paragraph field.
   */
  protected function createField($form_path, $field_name, $cardinality, $type, $widget, array $bundles = array()) {
    // Add a singleton field_example_text field.
    $this->drupalGet($form_path);
    $edit = array(
      'new_storage_type' => $type,
      'label' => $field_name,
      'field_name' => $field_name,
    );
    $this->drupalPostForm(NULL, $edit, t('Save and continue'));
    $edit = array(
      'cardinality_number' => (string) $cardinality,
    );
    if($cardinality === -1){
      $edit = array(
        'cardinality' => "-1",
      );
    }
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
    // There are no settings for this, so just press the button.
    $edit = array();
    if (isset($bundles) && count($bundles)) {
      foreach ($bundles as $bundle) {
        $edit['settings[handler_settings][target_bundles_drag_drop][' . $bundle .'][enabled]'] = '1';
      }
    }
    // Using all the default settings, so press the button.
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    #$message = format_string("Field @field added successfully", array("@field" => $field_name));
    $this->assertSession()->pageTextContains(t('Saved @name configuration.', array('@name' => $field_name)));
  }
}