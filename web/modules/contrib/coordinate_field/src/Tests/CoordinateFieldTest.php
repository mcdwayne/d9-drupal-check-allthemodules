<?php

namespace Drupal\coordinate_field\Tests;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\simpletest\WebTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests coordinate_field field widgets and formatters.
 *
 * @group coordinate_field
 */
class CoordinateFieldTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_test', 'coordinate_field', 'node'];

  /**
   * A field to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The instance used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
      'link to any page',
    ]));

  }

  public function testFieldDefaultFormatter() {

    $field_name = Unicode::strtolower($this->randomMachineName());

    // Create a field with settings to validate.
    $this->fieldStorage = FieldStorageConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'coordinate_field',
    ));
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'label' => 'Enter location coordinates',
    ]);
    $this->field->save();

    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, array(
        'type' => 'coordinate_default',
      ))
      ->save();

    entity_get_display('entity_test', 'entity_test', 'full')
      ->setComponent($field_name, array(
        'type' => 'coordinate_default',
        'label' => 'hidden',
      ))
      ->save();

    $this->drupalGet('entity_test/add');

    $edit = array(
      "{$field_name}[0][xpos]" => '20',
      "{$field_name}[0][ypos]" => '30',
    );

    $this->assertText('Enter location coordinates');
    $this->drupalPostForm(NULL, $edit, t('Save'));

    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)));

    $this->renderTestEntity($id);
    $this->assertText('20, 30');

  }

  public function testFieldSingleFormatter() {

    $field_name = Unicode::strtolower($this->randomMachineName());

    // Create a field with settings to validate.
    $this->fieldStorage = FieldStorageConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'coordinate_field',
    ));
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'label' => 'Enter location coordinates',
    ]);
    $this->field->save();

    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, array(
        'type' => 'coordinate_default',
      ))
      ->save();

    entity_get_display('entity_test', 'entity_test', 'full')
      ->setComponent($field_name, array(
        'type' => 'coordinate_single',
        'label' => 'hidden',
        'settings' => array(
          'element' => 'xpos',
        ),
      ))
      ->save();

    $this->drupalGet('entity_test/add');

    $edit = array(
      "{$field_name}[0][xpos]" => '20',
      "{$field_name}[0][ypos]" => '30',
    );

    $this->assertText('Enter location coordinates');
    $this->drupalPostForm(NULL, $edit, t('Save'));

    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)));

    $this->renderTestEntity($id);
    $this->assertText('20');

  }

  /**
   * Renders a test_entity and sets the output in the internal browser.
   *
   * @param int $id
   *   The test_entity ID to render.
   * @param string $view_mode
   *   (optional) The view mode to use for rendering.
   * @param bool $reset
   *   (optional) Whether to reset the entity_test storage cache. Defaults to
   *   TRUE to simplify testing.
   */
  protected function renderTestEntity($id, $view_mode = 'full', $reset = TRUE) {
    if ($reset) {
      $this->container->get('entity.manager')->getStorage('entity_test')->resetCache(array($id));
    }
    $entity = entity_load('entity_test', $id);
    $display = entity_get_display($entity->getEntityTypeId(), $entity->bundle(), $view_mode);
    $content = $display->build($entity);
    $output = \Drupal::service('renderer')->renderRoot($content);
    $this->setRawContent($output);
    $this->verbose($output);
  }
}
