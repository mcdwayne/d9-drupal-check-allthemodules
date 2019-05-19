<?php

namespace Drupal\Tests\textimage\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\textimage\Kernel\TextimageTestTrait;

/**
 * Base test class for Textimage tests.
 */
abstract class TextimageTestBase extends BrowserTestBase {

  use TextimageTestTrait;

  protected $textimageAdmin = 'admin/config/media/textimage';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['textimage', 'node', 'image_effects'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->initTextimageTest();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }

    // Create a user and log it in.
    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'create article content',
      'edit any article content',
      'delete any article content',
      'administer site configuration',
      'administer image styles',
      'generate textimage url derivatives',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Create a new Text field for the Textimage formatter.
   *
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $bundle
   *   The node type that this field will be added to.
   * @param array $storage_settings
   *   A list of field storage settings that will be added to the defaults.
   * @param array $field_settings
   *   A list of field settings that will be added to the field defaults.
   * @param array $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   */
  protected function createTextField($name, $bundle, array $storage_settings = [], array $field_settings = [], array $widget_settings = []) {
    FieldStorageConfig::create([
      'field_name' => $name,
      'entity_type' => 'node',
      'type' => 'text',
      'settings' => $storage_settings,
      'cardinality' => !empty($storage_settings['cardinality']) ? $storage_settings['cardinality'] : 1,
    ])->save();

    $field_config = FieldConfig::create([
      'field_name' => $name,
      'label' => $name,
      'entity_type' => 'node',
      'bundle' => $bundle,
      'required' => !empty($field_settings['required']),
      'description' => !empty($field_settings['description']) ? $field_settings['description'] : '',
      'settings' => $field_settings,
    ])->save();

    $this->entityDisplayRepository->getFormDisplay('node', $bundle, 'default')
      ->setComponent($name, [
        'type' => 'text_textfield',
        'settings' => $widget_settings,
      ])
      ->save();

    $this->entityDisplayRepository->getViewDisplay('node', $bundle, 'default')
      ->setComponent($name)
      ->save();

    return $field_config;
  }

  /**
   * Create a node.
   *
   * @param string $field_type
   *   Type of the field formatted by Textimage.
   * @param string $field_name
   *   Name of the field formatted by Textimage.
   * @param string $field_value
   *   Value of the field formatted by Textimage.
   * @param string $bundle
   *   The type of node to create.
   * @param string $node_title
   *   The title of node to create.
   */
  protected function createTextimageNode($field_type, $field_name, $field_value, $bundle, $node_title) {
    switch ($field_type) {
      case 'text':
        if (!is_array($field_value)) {
          $field_value = [$field_value];
        }
        $edit = [
          'title[0][value]' => $node_title,
          'body[0][value]' => $field_value[0],
        ];
        for ($i = 0; $i < count($field_value); $i++) {
          $index = $field_name . '[' . $i . '][value]';
          $edit[$index] = $field_value[$i];
        }
        $this->drupalPostForm('node/add/' . $bundle, $edit, t('Save'));
        break;

      case 'image':
        $edit = [
          'title[0][value]' => $node_title,
        ];
        $edit['files[' . $field_name . '_0]'] = $this->fileSystem->realpath($field_value->uri);
        $this->drupalPostForm('node/add/' . $bundle, $edit, t('Save'));
        // Add alt text.
        $this->drupalPostForm(NULL, [$field_name . '[0][alt]' => 'test alt text'], t('Save'));
        break;

    }

    // Retrieve ID of the newly created node from the current URL.
    $matches = [];
    preg_match('/node\/([0-9]+)/', $this->getUrl(), $matches);
    return isset($matches[1]) ? $matches[1] : FALSE;
  }

}
