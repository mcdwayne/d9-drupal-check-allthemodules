<?php

/**
 * @file
 * Contains \Drupal\rut_field\Tests\RutFieldTest.
 */

namespace Drupal\rut_field\Tests;

use Drupal\simpletest\WebTestBase;
use Tifon\Rut\RutUtil;

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
  public static $modules = array(
    'field',
    'node',
    'rut_field'
  );

  /**
   * A user with permission to create articles.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'article'));
    $this->webUser = $this->drupalCreateUser(array('create article content', 'edit own article content'));
    $this->drupalLogin($this->webUser);
  }

  // Test fields.

  /**
   * Helper function for testRutField().
   */
  function testRutField() {

    // Add the rut_field field to the article content type.
    entity_create('field_storage_config', array(
      'field_name' => 'field_rut_field',
      'entity_type' => 'node',
      'type' => 'rut_field_rut',
    ))->save();
    entity_create('field_config', array(
      'field_name' => 'field_rut_field',
      'label' => 'Rut',
      'entity_type' => 'node',
      'bundle' => 'article',
    ))->save();

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
    $randomRut = RutUtil::generateRut();
    $edit = array(
      'title[0][value]' => $this->randomMachineName(),
      'field_rut_field[0][value]' => $randomRut,
    );

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw($randomRut, 'A rut is provided on the article node page.');

    // Add rut without format. Then check if show the same rut with format.
    list($rut, $dv) = RutUtil::generateRut(FALSE);
    $edit = array(
      'title[0][value]' => $this->randomMachineName(),
      'field_rut_field[0][value]' => $rut . $dv,
    );

    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $this->assertRaw(RutUtil::formatterRut($rut, $dv), 'A rut formatted is provided on the article node page.');
  }
}
