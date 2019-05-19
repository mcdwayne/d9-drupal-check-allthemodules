<?php

namespace Drupal\rut_field\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\rut\Rut;

/**
 * Tests the creation of rut_field fields.
 *
 * @group rut_field
 */
class RutFieldTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'node',
    'rut_field'
  ];

  /**
   * A user with permission to create articles.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article']);
    $this->webUser = $this->drupalCreateUser(['create article content', 'edit own article content']);
    $this->drupalLogin($this->webUser);
  }

  // Test fields.

  /**
   * Helper function for testRutField().
   */
  function testRutField() {

    // Add the rut_field field to the article content type.
    entity_create('field_storage_config', [
      'field_name' => 'field_rut_field',
      'entity_type' => 'node',
      'type' => 'rut_field_rut',
    ])->save();
    entity_create('field_config', [
      'field_name' => 'field_rut_field',
      'label' => 'Rut',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();

    entity_get_form_display('node', 'article', 'default')
    ->setComponent('field_rut_field')
    ->save();

    entity_get_display('node', 'article', 'default')
    ->setComponent('field_rut_field')
    ->save();

    // Display creation form.
    $this->drupalGet('node/add/article');
    $this->assertFieldByName("field_rut_field[0][value]", '', 'Widget found.');

    // Test basic entry of rut_field field.
    $randomRut = Rut::generateRut();
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'field_rut_field[0][value]' => $randomRut,
    ];

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw($randomRut, 'A rut is provided on the article node page.');

    // Add rut without format. Then check if show the same rut with format.
    list($rut, $dv) = Rut::generateRut(FALSE);
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'field_rut_field[0][value]' => $rut . $dv,
    ];
    $rutFormatted = Rut::formatterRut($rut, $dv);
    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $this->assertRaw($rutFormatted, 'A rut formatted is provided on the article node page.');

    // Edit the node.
    $this->drupalGet('node/2/edit');
    $this->assertFieldByName("field_rut_field[0][value]", $rutFormatted, 'Widget found with the rut.');
    $randomRut = Rut::generateRut();
    $edit = [
      'field_rut_field[0][value]' => $randomRut,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw($randomRut, 'The rut was edited.');
  }
}
